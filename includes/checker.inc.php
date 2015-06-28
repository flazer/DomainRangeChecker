<?php
class RecursiveChecker{

    public $config = array();
    public $dnsObj = null;


    public function __construct($config){
        $this->config = $config;
    }

    public function run($width, $position, $base_string){
        $charset = $this->config['charset'];
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
                    echo "free!\n";
                    break;

                case 0:
                    echo "redemption!\n";
                    break;

                default:
                    echo "in use.\n";
                    break;
            }
            sleep($this->config['dns-sleep-secs']);
        }
    }

    public function doCheck($domain){
        if($this->doDNS($domain)){
            return 1;
        }else{
            return $this->doWhois($domain);
        }
    }

    public function doWhois($domain){
        exec("whois -h whois.denic.de -- -T dn ".$domain, $output, $result);
        //We need to sleep, otherwise denic will ban us. :/
        sleep($this->config['denic-sleep-secs']);
        if(is_array($output) && isset($output[1]) && $output[1] == 'Status: free'){
            return -1;
        }

        if(is_array($output) && isset($output[1]) && $output[1] == 'Status: redemptionPeriod'){
            return 0;
        }


        if(is_array($output) && !isset($output[1]) && strpos($output[0], 'Error: 55000000002') !== false){
            $coolDownSecs = $this->config['denic-cooldown-secs'];
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

    public function doDNS($domain){
        exec("host -t ANY ".$domain, $output, $result);
        if(is_array($output) && count($output) <= 1 && strpos($output[0], 'NXDOMAIN') !== false){
            return false;
        }
        return true;
    }

}

?>
