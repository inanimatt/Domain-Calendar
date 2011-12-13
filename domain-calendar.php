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

      $output->writeln(sprintf('Adding domain %s', $domain));

      try {
        $app['db']->executeQuery('INSERT INTO domains (domain_name) VALUES (?)', array($domain));
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
        $domains = $app['db']->fetchAll('SELECT domain_name FROM domains ORDER BY domain_name');
      } 
      catch (Exception $e)
      {
        $output->writeln(sprintf('<error>Unexpected error: %s</error>', $e->getMessage()));  
      }
      
      foreach($domains as $d)
      {
        $output->writeln("\t".$d['domain_name']);
      }
      
    }
  );

$console->run();