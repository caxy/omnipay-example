<?php

use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;
use Symfony\Component\HttpFoundation\Request;

class OmnipayExampleServiceProvider implements \Pimple\ServiceProviderInterface, \Silex\Api\BootableProviderInterface, \Silex\Api\ControllerProviderInterface
{

    /**
     * Returns routes to connect to the given application.
     *
     * @param \Silex\Application $app An Application instance
     *
     * @return \Silex\ControllerCollection A ControllerCollection instance
     */
    public function connect(\Silex\Application $app)
    {
        $controllers = $app['controllers_factory'];

        // gateway settings
        $controllers->get('/{name}', function($name) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
            $gateway = Omnipay::create($name);
            $sessionVar = 'omnipay.'.$gateway->getShortName();
            $gateway->initialize((array) $app['session']->get($sessionVar));

            return $app['twig']->render('gateway.twig', array(
              'gateway' => $gateway,
              'settings' => $gateway->getParameters(),
            ));
        });

        // save gateway settings
        $controllers->post('/{name}', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/authorize', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->post('/{name}/authorize', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/completeAuthorize', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/capture', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->post('/{name}/capture', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/purchase', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->post('/{name}/purchase', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->match('/{name}/completePurchase', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/create-card', function($name) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->post('/{name}/create-card', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/update-card', function($name) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->post('/{name}/update-card', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->get('/{name}/delete-card', function($name) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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
        $controllers->post('/{name}/delete-card', function($name, Request $request) use ($app) {
            /** @var \Omnipay\Common\GatewayInterface $gateway */
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

        return $controllers;
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param \Pimple\Container $pimple A container instance
     */
    public function register(\Pimple\Container $pimple)
    {
        // TODO: Implement register() method.
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param \Silex\Application $app
     */
    public function boot(\Silex\Application $app)
    {
        $app->mount('/gateways', $this->connect($app));
    }
}