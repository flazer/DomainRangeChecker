<?php
class RecursiveChecker{

    public $config = array();
    public $db = null;

    public function __construct($config){
        $this->config = $config;
    }


    public function run($width, $position, $base_string){
        $charset = $this->_getConfig('charset');
        $charset_length = strlen($charset);
        for ($i = 0; $i < $charset_length; ++$i) {
            if ($position  < $width - 1) {
                $this->run($width, $position + 1, $base_string . $charset[$i]);
            }
            $domain = $base_string . $charset[$i]. '.de';
            echo date('Y-m-d H:i:s', time())." checking: ".$domain." Status: ";
            $res = $this->doCheck($domain);
            switch ($res){
                case -1:
                    $status = 'free';
                    break;

                case 0:
                    $status = 'redemption';
                    break;

                default:
                    $status = 'used';
                    break;
            }
            echo $status.".\n";
            $this->_insert(array(
                'domain' => $domain,
                'status' => $status,
                'insert' => date('Y-m-d H:i:s', time())
                )
            );
            sleep($this->_getConfig('dns.sleep-secs'));
        }
    }


    /**
     * Checks domainstatus
     *
     * returns -1 if domain is free
     * returns 0 if domain is in redemption
     * returns 1 if domain is in use
     *
     * @param $domain
     * @return int
     */
    public function doCheck($domain){
        if($this->doDNS($domain)){
            return 1;
        }else{
            return $this->doWhois($domain);
        }
    }


    /**
     * Requests denic-api
     * for valid WhoIs-Data
     *
     * returns -1 if domain is free
     * returns 0 if domain is in redemption
     * returns 1 if domain is in use
     *
     * @param $domain
     * @return int
     */
    public function doWhois($domain){
        exec("whois -h whois.denic.de -- -T dn ".$domain, $output, $result);
        //We need to sleep, otherwise denic will ban us. :/
        sleep($this->_getConfig('denic.sleep-secs'));
        if(is_array($output) && isset($output[1]) && $output[1] == 'Status: free'){
            return -1;
        }

        if(is_array($output) && isset($output[1]) && $output[1] == 'Status: redemptionPeriod'){
            return 0;
        }


        if(is_array($output) && !isset($output[1]) && strpos($output[0], 'Error: 55000000002') !== false){
            $coolDownSecs = $this->_getConfig('denic.cooldown-secs');
            echo "got following message. (caused cool-down ".$coolDownSecs."sec):\n";
            echo json_encode($output)."\n";
            sleep($coolDownSecs);
            return $this->doWhois($domain);
        }


        if(is_array($output) && isset($output[1]) && strpos($output[1], 'Version:') === false){
            echo "got following unknown message. (caused quit):\n";
            echo json_encode($output)."\n";
            die();
        }
        return 1;
    }


    /**
     * Check Domain by DNS-Entries.
     * If check fails do denic-whois
     *
     * -1 = free
     *  0 = redemption
     *  1 = use
     *
     * @param $domain
     * @return int
     */
    public function doDNS($domain){
        exec("host -t ANY ".$domain, $output, $result);
        if(is_array($output) && count($output) <= 1 && strpos($output[0], 'NXDOMAIN') !== false){
            return false;
        }
        return true;
    }


    /**
     * Inserts dataset into
     * mysql-database
     *
     * @param $data
     * @return bool
     */
    private function _insert($data){
        if(method_exists($this->_getDatabase(), 'query')){
            $input = $this->_buildValues($data);

            $this->_getDatabase()->query("
            INSERT INTO `domainlist` (".$input['fields'].")
                VALUES (".$input['values'].")
            ON DUPLICATE KEY UPDATE
                `Status`='".$data['status']."',
                `Update`='".$data['insert']."'
            ");

            return true;
        }
        return false;
    }


    /**
     * Loads database if
     * necessary
     *
     * @return mysqli|null
     */
    private function _getDatabase(){
        $config = $this->_getConfig('database');
        if($this->db == null && $this->_checkForDatabase()){
            $this->db = mysqli_connect(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['name'])
            or die(
                "Error " . mysqli_error($this->db)
            );
        }

        return $this->db;
    }


    /**
     * Builds keys&values
     * for a valid SQL-Statement
     *
     * @param $data
     * @return array
     */
    private function _buildValues($data){
        $fields = '';
        $values = '';

        foreach($data AS $key => $val){
            $fields .=  '`'.ucfirst($key).'`,';
            $values .= '\''.$val.'\',';
        }

        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);

        return array(
            'fields' => $fields,
            'values' => $values
        );
    }


    /**
     * Checks if we have a
     * valid config to connect
     * to a database
     *
     * @return bool
     */
    private function _checkForDatabase(){
        $config = $this->_getConfig('database');
        if(!$config || !is_array($config) || !isset($config['host']) || strlen($config['host']) <= 0){
            return false;
        }
        return true;
    }


    /**
     * Small config-handling
     *
     * @param $key
     * @return mixed
     */
    private function _getConfig($key){
        if(strpos($key, '.') !== false){
            $keys = explode('.', $key);
            if(isset($this->config[$keys[0]][$keys[1]])){
                return $this->config[$keys[0]][$keys[1]];
            }
        }

        if(isset($this->config[$key])){
            return $this->config[$key];
        }
        return false;
    }
}

?>
