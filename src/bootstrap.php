<?php

require __DIR__.'/../vendor/silex.phar';

require __DIR__.'/../vendor/phpwhois/whois.main.php';

$app = new Silex\Application();
$app['debug'] = true;

$app['autoloader']->registerNamespace('Symfony', __DIR__ . '/../vendor/symfony/console');
$app['autoloader']->registerNamespace('Inanimatt', __DIR__);

/* Services */

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.dbal.class_path'    => __DIR__.'/../vendor/doctrine/dbal/lib',
    'db.common.class_path'  => __DIR__.'/../vendor/doctrine/common/lib',
    'db.options'            => array(
        'driver'    => 'pdo_sqlite',
        'path'      => __DIR__.'/../data/domain_calendar.db',
    ),
));

$app['whois'] = new Whois();

$app['domain_calendar'] = function() use ($app) {
  return new Inanimatt\DomainCalendarService($app['db'], $app['whois']);
};


// Create and migrate database if required
require_once __DIR__.'/check_db_schema.php';

return $app;

