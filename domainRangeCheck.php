<?php

/*
 Quick and Dirty Domain-Range-Checker-Tool for DE-Domains
--------------------------------------------------------

This script will check all possible domains in a specified
range. It's only working with Denic(DE)-Domains for now.

Example (cli):
php domainRangeCheck.php >> output

Output-example:

2015-05-30 12:21:00 checking: aaa.de Status: use.
2015-05-30 12:21:08 checking: aab.de Status: use.
2015-05-30 12:21:16 checking: aac.de Status: use.
2015-05-30 12:21:24 checking: aad.de Status: use.
2015-05-30 12:21:32 checking: aae.de Status: use.

If you want to store the results into a MySQL-Database,
you can use the following statement, to create a table:

-----

CREATE TABLE IF NOT EXISTS `domainlist` (
  `DomainId` int(11) NOT NULL,
  `Domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Insert` datetime NOT NULL,
  `Update` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ADD PRIMARY KEY (`DomainId`), ADD UNIQUE KEY `Domain` (`Domain`);
MODIFY `DomainId` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

-----

HOW does the script work:

It's querying a DNS-Server for a valid record.
If this fails, it will start a whois-query against denic-service.

Maybe you are asking, why it's not always using denic directly
and strip out the dns-queries:
DNS-Queries are much faster and will not block us, after a certain
time. So we can scan much faster.

Unfortunately i couldn't find any information about denic's allowed
query-intervals. I took 8 seconds as default and 120 seconds for a 
cooldown if we get banned. Which is very slow, but safe in my opinion.
*/

require_once('includes/RecursiveChecker.class.php');

$config = array(
    'dns' => array(
        'sleep-secs' => 0.1, //sleep after every dns-query
    ),
    'denic' => array(
        'sleep-secs' => 8, //sleep after every denic-call
        'cooldown-secs' => 120, //sleep when denic banned us
    ),
    'database' => array(
        'host' => '',
        'user' => '',
        'pass' => '',
        'name' => ''
    ),
    'charset' => 'abcdefghijklmnopqrstuvwxyz0123456789'
);

$RecursiveChecker = new RecursiveChecker($config);

$RecursiveChecker->run(3, 0, '');
echo "/n";
