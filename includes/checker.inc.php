<?php
require_once('dns.inc.php');

class RecursiveChecker{

    public $config = array();
    public $dnsObj = null;


    public function __construct($config){
        $this->config = $config;
        $this->dnsObj = new DNSQuery($config['dns-server']);
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
            $res = $this->doCheck($this->dnsObj, $domain);
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

    public function doCheck($dns, $domain){
        if($this->doDNS($dns, $domain)){
            return 1;
        }else{
            return $this->doWhois($domain);
        }
    }

    public function doDNS($obj, $domain){
        $result=$obj->SmartALookup($domain,5);
        if(strlen($result) <= 0){
            return true;
        }
        return false;
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

        if(is_array($output) && isset($output[1]) && strpos($output[1], 'Version:') === false){
            echo "got following unknown message. (caused quit):\n";
            echo json_encode($output)."\n";
            die();
        }
        return 1;
    }


}

?>