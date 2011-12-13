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

$console->run();