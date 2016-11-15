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

// gateway settings
$app->get('/gateways/{name}', function($name) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    return $app['twig']->render('gateway.twig', array(
        'gateway' => $gateway,
        'settings' => $gateway->getParameters(),
    ));
});

// save gateway settings
$app->post('/gateways/{name}', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $request->get('gateway'));

    // save gateway settings in session
    $app['session']->set($sessionVar, $gateway->getParameters());

    // redirect back to gateway settings page
    $app['session']->getFlashBag()->add('success', 'Gateway settings updated!');

    return $app->redirect($request->getBaseUrl() . $request->getPathInfo());
});

// create gateway authorize
$app->get('/gateways/{name}/authorize', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.authorize', array());
    $params['returnUrl'] = str_replace('/authorize', '/completeAuthorize', $request->getUri());
    $params['cancelUrl'] = $request->getUri();
    $card = new CreditCard($app['session']->get($sessionVar.'.card'));

    return $app['twig']->render('request.twig', array(
        'gateway' => $gateway,
        'method' => 'authorize',
        'params' => $params,
        'card' => $card->getParameters(),
    ));
});

// submit gateway authorize
$app->post('/gateways/{name}/authorize', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load POST data
    $params = $request->get('params');
    $card = $request->get('card');

    // save POST data into session
    $app['session']->set($sessionVar.'.authorize', $params);
    $app['session']->set($sessionVar.'.card', $card);

    $params['card'] = $card;
    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->authorize($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// create gateway completeAuthorize
$app->get('/gateways/{name}/completeAuthorize', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.authorize');
    $response = $gateway->completeAuthorize($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// create gateway capture
$app->get('/gateways/{name}/capture', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.capture', array());

    return $app['twig']->render('request.twig', array(
        'gateway' => $gateway,
        'method' => 'capture',
        'params' => $params,
    ));
});

// submit gateway capture
$app->post('/gateways/{name}/capture', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load POST data
    $params = $request->get('params');

    // save POST data into session
    $app['session']->set($sessionVar.'.capture', $params);

    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->capture($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// create gateway purchase
$app->get('/gateways/{name}/purchase', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.purchase', array());
    $params['returnUrl'] = str_replace('/purchase', '/completePurchase', $request->getUri());
    $params['cancelUrl'] = $request->getUri();
    $card = new CreditCard($app['session']->get($sessionVar.'.card'));

    return $app['twig']->render('request.twig', array(
        'gateway' => $gateway,
        'method' => 'purchase',
        'params' => $params,
        'card' => $card->getParameters(),
    ));
});

// submit gateway purchase
$app->post('/gateways/{name}/purchase', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load POST data
    $params = $request->get('params');
    $card = $request->get('card');

    // save POST data into session
    $app['session']->set($sessionVar.'.purchase', $params);
    $app['session']->set($sessionVar.'.card', $card);

    $params['card'] = $card;
    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->purchase($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// gateway purchase return
// this won't work for gateways which require an internet-accessible URL (yet)
$app->match('/gateways/{name}/completePurchase', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load request data from session
    $params = $app['session']->get($sessionVar.'.purchase', array());

    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->completePurchase($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// create gateway create Credit Card
$app->get('/gateways/{name}/create-card', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.create', array());
    $card = new CreditCard($app['session']->get($sessionVar.'.card'));

    return $app['twig']->render('request.twig', array(
        'gateway' => $gateway,
        'method' => 'createCard',
        'params' => $params,
        'card' => $card->getParameters(),
    ));
});

// submit gateway create Credit Card
$app->post('/gateways/{name}/create-card', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load POST data
    $params = $request->get('params');
    $card = $request->get('card');

    // save POST data into session
    $app['session']->set($sessionVar.'.create', $params);
    $app['session']->set($sessionVar.'.card', $card);

    $params['card'] = $card;
    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->createCard($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// create gateway update Credit Card
$app->get('/gateways/{name}/update-card', function($name) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.update', array());
    $card = new CreditCard($app['session']->get($sessionVar.'.card'));

    return $app['twig']->render('request.twig', array(
        'gateway' => $gateway,
        'method' => 'updateCard',
        'params' => $params,
        'card' => $card->getParameters(),
    ));
});

// submit gateway update Credit Card
$app->post('/gateways/{name}/update-card', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load POST data
    $params = $request->get('params');
    $card = $request->get('card');

    // save POST data into session
    $app['session']->set($sessionVar.'.update', $params);
    $app['session']->set($sessionVar.'.card', $card);

    $params['card'] = $card;
    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->updateCard($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

// create gateway delete Credit Card
$app->get('/gateways/{name}/delete-card', function($name) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    $params = $app['session']->get($sessionVar.'.delete', array());

    return $app['twig']->render('request.twig', array(
        'gateway' => $gateway,
        'method' => 'deleteCard',
        'params' => $params,
    ));
});

// submit gateway delete Credit Card
$app->post('/gateways/{name}/delete-card', function($name, Request $request) use ($app) {
    $gateway = Omnipay::create($name);
    $sessionVar = 'omnipay.'.$gateway->getShortName();
    $gateway->initialize((array) $app['session']->get($sessionVar));

    // load POST data
    $params = $request->get('params');

    // save POST data into session
    $app['session']->set($sessionVar.'.delete', $params);

    $params['clientIp'] = $request->getClientIp();
    $response = $gateway->deleteCard($params)->send();

    return $app['twig']->render('response.twig', array(
        'gateway' => $gateway,
        'response' => $response,
    ));
});

$app->run();
