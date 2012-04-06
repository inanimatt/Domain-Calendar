Domain Calendar
===============

This console application generates an iCalendar format file with reminders set for the expiry dates of one or more domain names. Registrars usually send you emails or offer auto-renewal, but credit cards often expire and emails get lost or marked as spam. Keeping expiry dates in your calendar is a simple way to make sure you don't accidentally let a domain name expire.

Pull requests welcome. Domain Calendar is MIT licensed.


Requirements
------------

* PHP 5.3+


Installation
------------

**Note:** the easiest way to get started is to get the latest complete installation from the [download](https://github.com/inanimatt/Domain-Calendar/downloads) page on GitHub. These packages include all the required libraries and dependencies, but may be older than the current development version.

This project requires Silex, the Symfony2 Console component, Doctrine DBAL, and PHPWhois. A Composer file is included to download and install the first three of these, unfortunately it doesn't yet support `tar.gz` files or CVS, so you'll have to download and install PHPWhois manually.

* From the command line, run `curl -s http://getcomposer.org/installer | php` to download composer.
* Run `php composer.phar install` to fetch Doctrine and Silex.
* Download [PHPWhois](http://sourceforge.net/projects/phpwhois/files/phpwhois/) and unpack it into the `vendor/phpwhois` directory
* Run `php vendor/composer.phar install`
* Make sure the `data` directory is writeable by your user

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

You can quell all non-error output from the above commands by adding the `--quiet` option, which makes it possible to use in a cronjob. For example, to get a valid ICS file from the command-line without specifying a filename argument:

`./domain-calendar calendar:generate --quiet > test.ics`


TODO
----

* Web UI?
* Test suite!
* Don't migrate DB schema automatically: throw an exception then provide a console command to display potential data-loss warning and handle migration (`./domain-calendar database:upgrade --force` -- also `database:dump`?)
* Generate a single reminder for multiple domains expiring the same day
