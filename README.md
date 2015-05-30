Quick and Dirty Domain-Range-Checker-Tool for DE-Domains
--------------------------------------------------------

This script will check all possible domains in a specified
range. It's only working with Denic(DE)-Domains for now.

Example (cli):
php domainRangeCheck.php >> output

Output-example:
2015-05-30 12:21:00 checking: aaa.de Status: in use.
2015-05-30 12:21:08 checking: aab.de Status: in use.
2015-05-30 12:21:16 checking: aac.de Status: in use.
2015-05-30 12:21:24 checking: aad.de Status: in use.
2015-05-30 12:21:32 checking: aae.de Status: in use.

It's using "PurplePixie PHP DNS Query Classes" for DNS-Queries.
Check them out on:
http://www.purplepixie.org/phpdns

HOW does the script work:

It's querying a DNS-Server for a valid A-Record.
If this fails, it will start a whois-query against denic-service.

Maybe you are asking, why it's not always using denic directly
and strip out the dns-queries:
DNS-Queries are much faster and will not block us, after a certain
time. So we can scan much faster.

Unfortunately i couldn't find any information about denic's allowed
query-intervals. I took 8 seconds as default. Which is very slow,
but safe in my opinion.
