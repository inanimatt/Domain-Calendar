<?php

require __DIR__.'/../vendor/silex.phar';

$app = new Silex\Application();
$app['debug'] = true;

/* Services */

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options'            => array(
        'driver'    => 'pdo_sqlite',
        'path'      => __DIR__.'/../data/domain_calendar.db',
    ),
    'db.dbal.class_path'    => __DIR__.'/../vendor/doctrine/dbal/lib',
    'db.common.class_path'  => __DIR__.'/../vendor/doctrine/common/lib',
));


$app['autoloader']->registerNamespace('Symfony', __DIR__ . '/../vendor/symfony/console');

// Create and migrate database if required
require_once __DIR__.'/check_db_schema.php';

return $app;

