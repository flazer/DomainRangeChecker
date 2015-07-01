Quick and Dirty Domain-Range-Checker-Tools for DE-Domains
--------------------------------------------------------

This domainRangeCheck-script will check all possible domains in a specified
range. It's only working with Denic(DE)-Domains for now.

Example (cli):
php domainRangeCheck.php >> output

Output-example:
2015-05-30 12:21:00 checking: aaa.de Status: in use.
2015-05-30 12:21:08 checking: aab.de Status: in use.
2015-05-30 12:21:16 checking: aac.de Status: in use.
2015-05-30 12:21:24 checking: aad.de Status: in use.
2015-05-30 12:21:32 checking: aae.de Status: in use.

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

---

This intervalChecker-script will check all domains which are stored
in your database. It will send a mail if a domainstatus changes.
Add it to your crontab, to let it run automatically.

---

You will find SQL-dumps for both scripts within the specific files.