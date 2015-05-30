<?php
require_once('includes/checker.inc.php');

$config = array(
    'dns-server' => '8.8.8.8',
    'dns-sleep-secs' => 0.1,
    'denic-sleep-secs' => 8,
    'charset' => 'abcdefghijklmnopqrstuvwxyz0123456789'
);

$RecursiveChecker = new RecursiveChecker($config);

$RecursiveChecker->run(3, 0, '');
echo "/n";
