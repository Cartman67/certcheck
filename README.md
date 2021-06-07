certcheck - Checks certificates for expiration

A simple php script that displays information on certificates used with https, smtp (tls), ldaps (tls), imaps, ftps and sends alerst per mail before they expire. Creates also a calendar entry (icalendar ics file) with a reminder.

When executed from shell/command line (for example in a weekly cronjob or scheduled task):
* Sends alert mails if a certificate is going to expire soon
* Sends the overview of all certificates per mail

When called in a web browser:
* Displays overview in a HTML table
* Adds a reminder to your calendar (via icalendar .ics file)

Tested with Windows 10&2016, Linux Slackware current and PHP 7.4 & 7.2
Supports https, imaps, smtps (tls) , pop3s, ldaps (tls), ftps

(c) 2020- Jan W - certcheck@kreator.org
