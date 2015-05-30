<?php
require_once('includes/checker.inc.php');

$config = array(
    'dns-server' => '8.8.8.8', //dns server to work with
    'dns-sleep-secs' => 0.1, //sleep after every dns-query
    'denic-sleep-secs' => 8, //sleep after every denic-call
    'denic-cooldown-secs' => 120, //sleep when denic banned us
    'charset' => 'abcdefghijklmnopqrstuvwxyz0123456789'
);

$RecursiveChecker = new RecursiveChecker($config);

$RecursiveChecker->run(3, 0, '');
echo "/n";
