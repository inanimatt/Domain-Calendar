<?php

require __DIR__.'/../vendor/silex.phar';

$app = new Silex\Application();

/* Services */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/../views',
    'twig.class_path' => __DIR__.'/../vendor/twig/twig/lib',
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SymfonyBridgesServiceProvider(), array(
    'symfony_bridges.class_path'  => __DIR__.'/../vendor/symfony/twig-bridge',
));


/* Routes and controllers */
$app->get('/', function() use ($app) {
    return $app['twig']->render('index.html.twig', array(
      'name' => 'world',
    ));
})
->bind('homepage');


/* Return configured app */
return $app;