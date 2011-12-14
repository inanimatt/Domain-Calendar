#!/usr/bin/env php
<?php

$app = require __DIR__.'/src/bootstrap.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Inanimatt\DomainCalendarException;


$console = new Application('DomainCalendar', '1.0.0');

$console->register('domain:add')
  ->setDefinition( array(
      new InputArgument('domain', InputArgument::REQUIRED, 'Fully qualified domain name'),
    ) )
  ->setDescription('Add a domain to the database')
  ->setHelp('Usage: <info>./domain-calendar.php domain:add domain</info>')
  ->setCode(
    function(InputInterface $input, OutputInterface $output) use ($app)
    {
      
      $domain = $input->getArgument('domain');
      
      $output->writeln(sprintf('Adding domain %s', $domain));
      
      try {
        $app['domain_calendar']->add($domain);
      } 
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>%s (%s)</error>', $e->getMessage(), get_class($e)));
        exit;
      }
      
    }
  );

$console->register('domain:remove')
  ->setDefinition( array(
      new InputArgument('domain', InputArgument::REQUIRED, 'Fully qualified domain name'),
    ) )
  ->setDescription('Remove domain from the database')
  ->setHelp('Usage: <info>./domain-calendar.php domain:remove domain</info>')
  ->setCode(
    function(InputInterface $input, OutputInterface $output) use ($app)
    {
      
      $domain = $input->getArgument('domain');

      $output->writeln(sprintf('Removing domain %s', $domain));

      try {
        $app['domain_calendar']->remove($domain);
      } 
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>%s (%s)</error>', $e->getMessage(), get_class($e)));
      }
      
    }
  );

$console->register('domain:list')
  ->setDefinition( array(
    ) )
  ->setDescription('List domains')
  ->setHelp('Usage: <info>./domain-calendar.php domain:list</info>')
  ->setCode(
    function(InputInterface $input, OutputInterface $output) use ($app)
    {
      
      $output->writeln('Domains:');

      try {
        $domains = $app['domain_calendar']->findAll();
      } 
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>%s (%s)</error>', $e->getMessage(), get_class($e)));
      }
      
      foreach($domains as $d)
      {
        $output->writeln(sprintf("%s\t%s", date_create($d['expires'])->format('Y-m-d'), $d['domain_name']));
      }
      
    }
  );

$console->register('domain:refresh-all')
  ->setDefinition( array(
      new InputOption('force-all', null, InputOption::VALUE_NONE, 'Don\'t skip cached domains with expiry dates in the future'),
    ) )
  ->setDescription('Update expiry info on all domain names')
  ->setHelp('Usage: <info>./domain-calendar.php domain:refresh-all</info>')
  ->setCode(
    function(InputInterface $input, OutputInterface $output) use ($app)
    {
      
      $output->writeln('Refreshing domain expiry dates');
      
      try {
        $results = $app['domain_calendar']->refresh($input->getOption('force-all'));
      }
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>%s (%s)</error>', $e->getMessage(), get_class($e)));
      }
      
      $output->writeln(join($results, PHP_EOL));
  
    }
  );

$console->register('calendar:generate')
  ->setDefinition( array(
      new InputOption('months', null, InputOption::VALUE_OPTIONAL, 'Remind n months before expiry', 0),
      new InputOption('days', null, InputOption::VALUE_OPTIONAL, 'Remind n days before expiry', 7),
      new InputOption('time', null, InputOption::VALUE_OPTIONAL, 'Time for the reminder in 24hr format (e.g. 14:00)', '14:00'),
      new InputArgument('filename', InputArgument::OPTIONAL, 'Output filename', 'php://stdout'),
    ) )
  ->setDescription('Generate a calendar file')
  ->setHelp('Usage: <info>./domain-calendar.php calendar:generate [filename]</info>
  
The default reminder is at 14:00, 7 days before expiry, in your 
PHP-configured timezone.

Use the --months= --days= and --time= options to change the reminders
in the calendar file. You can combine these, for example:

./domain-calendar.php calendar:generate --months=1 --days=3 --time=14:00

If you don\'t specify a filename, the calendar will be output to STDOUT.
  ')
  ->setCode(
    function(InputInterface $input, OutputInterface $output) use ($app)
    {
      
      $output->writeln('Generating calendar');
      
      $calendar = $app['domain_calendar']->generateCalendar($input->getOption('months'),$input->getOption('days'),$input->getOption('time'));
      
      $output->writeln('Saving calendar file');
      file_put_contents($input->getArgument('filename'), $calendar);

    }
  );

$console->run();