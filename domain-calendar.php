#!/usr/bin/env php
<?php

$app = require __DIR__.'/src/bootstrap.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


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

      $output->writeln('Requesting expiry date');
      $result = $app['whois']->Lookup($domain);
      
      if (!is_array($result) || !isset($result['regrinfo']) || !isset($result['regrinfo']['domain']) || !isset($result['regrinfo']['domain']['expires']))
      {
        $output->writeln('<error>Error requesting WHOIS record. Skipping.</error>');
        exit;
      }

      $expiry = new DateTime($result['regrinfo']['domain']['expires']);

      $output->writeln(sprintf('Adding domain %s', $domain));

      try {
        $app['db']->executeQuery('INSERT INTO domains (domain_name, expires) VALUES (?, ?)', array($domain, $expiry->format('Y-m-d')));
      } 
      catch (Exception $e)
      {
        if ($e->getMessage() == 'SQLSTATE[23000]: Integrity constraint violation: 19 column domain_name is not unique')
        {
          $output->writeln('<error>Failed: domain already in database</error>');
        } 
        else 
        {
          $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
        }
        
        
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
        $app['db']->executeQuery('DELETE FROM domains WHERE domain_name = ?', array($domain));
      } 
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
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
        $domains = $app['db']->fetchAll('SELECT domain_name, expires FROM domains ORDER BY domain_name');
      } 
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
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
      
      $output->writeln('Fetching domains...');
      
      try {
        $results = $app['db']->fetchAll('SELECT domain_name, expires FROM domains ORDER BY domain_name');
      }
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
      }
      
      foreach($results as $row)
      {
        $output->write(sprintf('%s: ', $row['domain_name']));
        
        if ($row['expires'] && (date_create($row['expires'])->format('U') > time()) && !$input->getOption('force-all'))
        {
          $output->writeln(sprintf('%s (cached, skipping)', $row['expires']));
          continue;
        }

        $result = $app['whois']->Lookup($row['domain_name']);
        
        if (!is_array($result) || !isset($result['regrinfo']) || !isset($result['regrinfo']['domain']) || !isset($result['regrinfo']['domain']['expires']))
        {
          $output->writeln('<error>Error requesting WHOIS record. Skipping.</error>');
          continue;
        }

        $expiry = new DateTime($result['regrinfo']['domain']['expires']);
        
        $output->writeln($expiry->format('Y-m-d'));
        
        try {
          $app['db']->executeQuery('UPDATE domains SET expires = ? WHERE domain_name = ?', array($expiry->format('Y-m-d'), $row['domain_name']));
        }
        catch (Exception $e)
        {
          $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
        }
        
      }

      $output->writeln('Done');
  
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
      
      $output->writeln('Fetching domains...');
      
      try {
        $results = $app['db']->fetchAll('SELECT domain_name, expires FROM domains WHERE expires > ? ORDER BY domain_name', array(date_create()->format('Y-m-d')));
      }
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
      }
      
      
      $calendar_template = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//inanimatt.com/DomainCalendar//NONSGML v1.0//EN
{events}
END:VCALENDAR
';
      
      $event_template = 'BEGIN:VEVENT
UID:{uid}
DTSTAMP:{dtstamp}
DTSTART:{start}
DTEND:{end}
SUMMARY:{summary}
BEGIN:VALARM
TRIGGER;VALUE=DATE-TIME:{reminder}
ACTION:DISPLAY
DESCRIPTION:{summary}
END:VALARM
END:VEVENT
';
      
      $events = array();
      foreach($results as $idx => $r)
      {
        $expiry = date_create($r['expires'], new DateTimeZone('UTC'));
        
        $reminder = date_create_from_format('Y-m-d', $r['expires']);
        $reminder->setTimeZone(new DateTimeZone('UTC'));
        
        if (!$reminder)
        {
          $output->writeln(sprintf('<error>Unable to parse expiry date for domain %s. Skipping.</error>', $r['domain_name']));
        }
        
        if ($input->getOption('months'))
        {
          $reminder->modify(sprintf('-%d months', $input->getOption('months')));
        }
        if ($input->getOption('days'))
        {
          $reminder->modify(sprintf('-%d days', $input->getOption('days')));
        }
        if ($input->getOption('time'))
        {
          if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $input->getOption('time')))
          {
            $output->writeln('<error>Invalid reminder time given</error>');
            exit;
          }
          
          $reminder->modify(sprintf('%s', $input->getOption('time')));
        }

        
        
        $output->writeln(sprintf('Domain %s expires %s, setting reminder for %s', $r['domain_name'], $r['expires'], $reminder->format('Y-m-d H:i T')));
        
        
        $event_data = array(
          '{uid}'       => sprintf('uid%d@%s', $idx, $r['domain_name']),
          '{dtstamp}'   => date_create()->format('Ymd\THis\Z'),
          '{start}'     => $expiry->format('Ymd\THis\Z'),
          '{end}'       => $expiry->format('Ymd\THis\Z'),
          '{summary}'   => sprintf('Domain %s expires on %s', $r['domain_name'], $expiry->format('l jS F')),
          '{reminder}'  => $reminder->format('Ymd\THis\Z'),
        );
        
        $events[] = strtr($event_template, $event_data);
      }
      
      $calendar = strtr($calendar_template, array('{events}' => join($events, PHP_EOL)));
      
      $output->writeln('Saving calendar file');
      file_put_contents($input->getArgument('filename'), $calendar);

    }
  );

$console->run();