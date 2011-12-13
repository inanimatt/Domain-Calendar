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
* Download [PHPWhois](http://sourceforge.net/projects/phpwhois/files/phpwhois/) and unpack it in the `vendor` directory
* Download the [Composer](http://getcomposer.org/composer.phar) package manager and put it in the `vendor` directory
* Run `php vendor/composer.phar install`
* Make sure the `data` directory is writeable by your username

Usage
-----

**Add a domain**
`domain-calendar domain:add example.com`

**Remove a domain**
`domain-calendar domain:remove example.com`

**List domains**
`domain-calendar domain:list`

**Refresh expiry information**
`domain-calendar domain:refresh-all`

**Generate calendar**
`domain-calendar calendar:generate --months=2 --days=5 [filename.ics]`

If you don't supply the month or day options, the calendar file is generated without reminders. 
If you don't supply a filename, the calendar is saved in `data/domains.ics`


TODO
----

* Abstract command code into one or more services
* Web UI?
* Test suite!

