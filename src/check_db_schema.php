<?php

if (!is_file(__DIR__.'/domain_calendar.db'))
{
  touch(__DIR__.'/domain_calendar.db');
}

try {
  $version = $app['db']->fetchColumn('SELECT db_version FROM version', array(), 0);
} 
catch (PDOException $e)
{
  /* Only want to catch the case where the database or version table doesn't exist
   * Let the exception be thrown in all other cases
   */
  if ($e->getMessage() != 'SQLSTATE[HY000]: General error: 1 no such table: version')
  {
    throw $e;
  }
  
  $version = 0;
}

$current_version = '2011-12-13';

if ($version != $current_version)
{    

  $sm = $app['db']->getSchemaManager();
  $fromSchema = $sm->createSchema();

  $toSchema = clone $fromSchema;
  $domainTable = $toSchema->createTable('domains');
  $domainTable->addColumn('id', 'integer', array('unsigned' => true));
  $domainTable->addColumn('domain_name', 'string', array('length' => 253));
  $domainTable->setPrimaryKey(array('id'));
  $domainTable->addUniqueIndex(array('domain_name'));

  $versionTable = $toSchema->createTable('version');
  $versionTable->addColumn('db_version', 'string', array('length' => 10));

  $sql = $fromSchema->getMigrateToSql($toSchema, $app['db']->getDatabasePlatform());
  
  foreach($sql as $query)
  {
    $app['db']->executeQuery($query);
  }
  
  $app['db']->executeQuery('INSERT INTO version (db_version) VALUES (?)', array($current_version));
  
}