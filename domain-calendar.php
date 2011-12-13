#!/usr/bin/env php
<?php

$app = require __DIR__.'/src/bootstrap.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


$console = new Application('DomainCalendar', '1.0.0');

$console->register('hello-world')
  ->setDefinition( array(
     //Create a "--test" optional parameter
     new InputOption('test', '', InputOption::VALUE_NONE, 'Test mode'),
    ) )
  ->setDescription('Say hello')
  ->setHelp('Usage: <info>./domain-calendar.php hello-world [--test]</info>')
  ->setCode(
    function(InputInterface $input, OutputInterface $output) use ($app)
    {
      if ($input->getOption('test'))
      {
        $output->write("\n\tTest Mode Enabled\n\n");
      }

      $output->write( "Hello World\n");
      //Do work here
      //Example:
      //  $app[ 'myExtension' ]->doStuff();
    }
  );

$console->run();