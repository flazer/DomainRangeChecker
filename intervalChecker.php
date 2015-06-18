<?php

/**
 *  Use this statement for your MySql to create the necessary table:


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `intervalchecks` (
`DomainId` int(11) NOT NULL,
`Domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`Status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
`Insert` datetime NOT NULL,
`Checked` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `intervalchecks`
ADD PRIMARY KEY (`DomainId`), ADD UNIQUE KEY `Domain` (`Domain`);


ALTER TABLE `intervalchecks`
MODIFY `DomainId` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

 *
 */


$config = array(
    'db' => array(
        'host' => 'localhost',
        'user' => '_USER',
        'pass' => '_PASSWORD_',
        'name' => '_DATABASE_'
    ),
    'denic' => array(
        'sleep-between-checks' => 8,
        'sleep-for-cooldown' => 120
    ),
    'notification' => array(
        'receiver' => 'receiver@provider.de',
        'sender' => 'sender@provider.de',
        'subject' => 'Domain-Change-Notification'
    )
);


$Config = new Config($config);
$Db = new Db($Config->getByKey('db'));
$Checker = new Checker($Config, $Db);

$Checker->run();


class Checker {

    private $_db = null;
    private $_config = null;


    private function _getDB(){
        return $this->_db;
    }


    private function _setDB($db){
        $this->_db = $db;
    }


    private function _getConfig(){
        return $this->_config;
    }


    private function _setConfig($config){
        $this->_config = $config;
    }


    public function __construct($config, $db){
        $this->_setDB($db);
        $this->_setConfig($config);
    }


    public function run(){
        $domains = $this->_getDomains();
        $changes = array();

        if(is_array($domains) && !empty($domains)){
            foreach($domains AS $row){
                $domain = $row['Domain'];
                echo date('Y-m-d H:i:s', time())." checking: ".$domain." Status: ";
                $res = $this->_checkDomain($domain);

                $status = 'use';
                switch ($res){
                    case -1:
                        echo "free!\n";
                        $status = 'free';
                        break;

                    case 0:
                        echo "redemption!\n";
                        $status = 'redemption';
                        break;

                    default:
                        echo "in use.\n";
                        break;
                }
                $this->_updateEntry((int) $row['DomainId'], $status);

                if($status != $row['Status']){
                    $changes[$domain] = array(
                        'from' => $row['Status'],
                        'to' => $status
                    );
                }
            }

            if(!empty($changes)){
                echo "----\nChanges detected. Sending mail!\n\n";
                $this->_sendMail($changes);
            }
        }
    }


    /**
     * Sends a mail with all
     * changes happened
     *
     * @param $changes
     */
    private function _sendMail($changes){
        $receiver = $this->_getConfig()->getByKey('notification.receiver');
        $sender   = $this->_getConfig()->getByKey('notification.sender');
        $subject  = $this->_getConfig()->getByKey('notification.subject');

        $header = 'From: '.$sender . "\r\n" .
            'Reply-To: '.$sender. "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        $message  = "The following domains have changed:\n\n";

        foreach($changes AS $domain => $change){
            $message .= $domain.': '.$change['from'].' -> '.$change['to']."\n";
        }

        $message .= "\n---\n";
        $message .= date('Y-m-d H:i:s', time());

        mail($receiver, $subject, $message, $header);
    }


    /**
     * Updates row by id in table
     *
     * @param $domainId
     * @param $status
     * @return mixed
     */
    private function _updateEntry($domainId, $status){
        return $this->_getDB()->connection()->query('
                    UPDATE intervalchecks
                        SET
                            `Status`= "'.$status.'",
                            `Checked`= "'.date('Y-m-d H:i:s', time()).'"
                        WHERE `DomainId` = '.$domainId
        );
    }


    /**
     * Gets domains from table to check
     *
     * @return array
     */
    private function _getDomains(){
        $data = array();
        $sql = 'SELECT * FROM intervalchecks';
        $result = $this->_getDB()->connection()->query($sql);;
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }

        return $data;
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
    private function _checkDomain($domain){
        $result = 1;

        exec("host -t ANY ".$domain, $output, $res);
        if(is_array($output) && count($output) <= 1 && strpos($output[0], 'NXDOMAIN') !== false){
            $result = -1;
        }

        if($result == -1){
            $result = $this->_doWhois($domain);
        }
        return $result;
    }


    /**
     * Check for valid WhoIs-Data
     *
     * returns -1 if domain is free
     * returns 0 if domain is in redemption
     * returns 1 if domain is in use
     *
     * @param $domain
     * @return int
     */
    private function _doWhois($domain){
        exec("whois -h whois.denic.de -- -T dn ".$domain, $output, $result);

        //We need to sleep, otherwise denic will ban us. :/
        sleep($this->_getConfig()->getByKey('denic.sleep-between-checks'));

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
            sleep($this->_getConfig()->getByKey('denic.sleep-for-cooldown'));
            return $this->_doWhois($domain);
        }


        if(is_array($output) && isset($output[1]) && strpos($output[1], 'Version:') === false){
            echo "got following unknown message. (caused quit):\n";
            echo json_encode($output)."\n";
            die();
        }

        return 1;
    }
}


/**
 * Class Config
 *
 * Small wrapper for Config-Param-Handling
 *
 */
class Config{
    private $_config = array();

    public function set($config){
        $this->_config = $config;
    }


    public function get(){
        return $this->_config;
    }


    public function __construct(array $config){
        $this->set($config);
    }


    public function getByKey($key){
        if(strpos($key, '.')){
            $key = explode('.', $key);
        }

        $config = $this->get();

        if(is_array($key)){
            if(isset($config[$key[0]][$key[1]])){
                return $config[$key[0]][$key[1]];
            }
        }

        if(isset($config[$key])){
            return $config[$key];
        }

        return false;
    }
}


/**
 * Class DB
 *
 * Small Wrapper-Class for DB-Handling
 *
 */
class DB {

    private $_db = null;
    private $_config = null;


    public function __construct($config){
        $this->_config = $config;;
    }


    private function _connect(){

        $mysqli = new mysqli(
            $this->_config['host'],
            $this->_config['user'],
            $this->_config['pass'],
            $this->_config['name']
        );

        if ($mysqli->connect_errno) {
            printf("Connect failed: %s\n", $mysqli->connect_error);
            exit();
        }

        $this->_db = $mysqli;
    }


    public function connection(){
        if($this->_db == null){
            $this->_connect();
        }

        return $this->_db;
    }
}

?>
