<?php

require __DIR__.'/../vendor/silex.phar';

$app = new Silex\Application();

$app->get('/', function() {
    return 'Hello';
});

return $app;