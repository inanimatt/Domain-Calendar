<?php

require __DIR__.'/../vendor/silex.phar';

$app = new Silex\Application();
$app['debug'] = true;

/* Services */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/../views',
    'twig.class_path' => __DIR__.'/../vendor/twig/twig/lib',
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SymfonyBridgesServiceProvider(), array(
    'symfony_bridges.class_path'  => __DIR__.'/../vendor/symfony/twig-bridge',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options'            => array(
        'driver'    => 'pdo_sqlite',
        'path'      => __DIR__.'/domain_calendar.db',
    ),
    'db.dbal.class_path'    => __DIR__.'/../vendor/doctrine/dbal/lib',
    'db.common.class_path'  => __DIR__.'/../vendor/doctrine/common/lib',
));


require_once __DIR__.'/check_db_schema.php';


/* Routes and controllers */
$app->get('/', function() use ($app) {
    
  
    return $app['twig']->render('index.html.twig', array(
      'name' => 'world',
    ));
})
->bind('homepage');


/* Return configured app */
return $app;