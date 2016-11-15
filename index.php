<?php

use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/vendor/autoload.php';

// create basic Silex application
$app = new Application();
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app->register(new \OmnipayExampleServiceProvider());

// enable Silex debugging
$app['debug'] = true;

// root route
$app->get('/', function() use ($app) {
    $gateways = array_map(function($name) {
        return Omnipay::create($name);
    }, Omnipay::find());

    return $app['twig']->render('index.twig', array(
        'gateways' => $gateways,
    ));
});

$app->run();
