Domain Calendar
===============

This console application generates an iCalendar format file with reminders set for the expiry dates of one or more domain names. Registrars usually send you emails or offer auto-renewal, but credit cards often expire and emails get lost or marked as spam. Keeping expiry dates in your calendar is a simple way to make sure you don't accidentally let a domain name expire.

Pull requests welcome. Domain Calendar is MIT licensed.


Requirements
------------

* PHP 5.3+


Installation
------------

This project uses Composer to track and manage project dependencies. However Composer doesn't yet handle the download of single files and archives, so there are a couple of additional steps required for now:

* Download the [Silex](http://silex.sensiolabs.org/get/silex.phar) micro-framework and put it in the `vendor` directory
* Download [PHPWhois](http://sourceforge.net/projects/phpwhois/files/phpwhois/) and unpack it into the `vendor/phpwhois` directory
* Download the [Composer](http://getcomposer.org/composer.phar) package manager and put it in the `vendor` directory
* Run `php vendor/composer.phar install`
* Make sure the `data` directory is writeable by your username

Usage
-----

**Add a domain**
`./domain-calendar domain:add example.com`

**Remove a domain**
`./domain-calendar domain:remove example.com`

**List domains**
`./domain-calendar domain:list`

Show stored domain names and cached expiry dates.

**Refresh expiry information**
`./domain-calendar domain:refresh-all`

Checks for updated information on all domain names with past expiry dates. Add `--force-all` to check all stored domain names instead.

**Generate calendar**
`./domain-calendar calendar:generate --months=2 --days=5 --time=14:00 [filename.ics]`

If you don't supply the month or day options, the calendar file is generated with reminders at 2pm, 7 days before expiry.

If you don't supply a filename, the calendar is output to STDOUT


Unattended/scheduled usage
--------------------------

You can quell all non-error output from the above commands by adding the `--quiet` option, which makes it possible to use in a cronjob.


TODO
----

* Abstract command code into one or more services
* Web UI?
* Test suite!
* Don't migrate DB schema automatically: throw an exception then provide a console command to display potential data-loss warning and handle migration (`./domain-calendar database:upgrade --force` -- also `database:dump`?)
* Generate a single reminder for multiple domains expiring the same day
