<?php

namespace UserFrosting\OAuth;

require_once('OAuthController.php');
require_once('OAuthUser.php');
require_once('OAuthUserLoader.php');

// TODO: Let Composer autoload all our classes

use UserFrosting as UF;

// Fetch the relevant controller
function getProviderController($provider, $callback_page, $app) {
    switch ($provider) {
        case "linkedin" :
            require_once('OAuthControllerLinkedIn.php');

            return new \UserFrosting\OAuth\OAuthControllerLinkedIn($app, $callback_page);

        default:
//            return false;
            $app->notFound();
    }
}

/* Import UserFrosting variables as global Twig variables */
$twig = $app->view()->getEnvironment();

$loader = $twig->getLoader();
// First look in user's theme...
$loader->addPath($app->config('plugins.path') . "/UserFrosting-OAuth/templates");

// Innitialize the OAuth User Loader the table and column definitions will be loaded
OAuthUserLoader::init();

// Define routes
// This is the GET route for the "login with ___" button
$app->get('/oauth/:provider/login', function ($provider) use ($app) {
    $controller = getProviderController($provider, 'login', $app);
    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "login";

    $get = $app->request->get();
    // If we received an authorization code, then resume our action
    if (isset($get['code'])) {
        // If we're logging them in, just call that method and it will automatically redirect us
        $controller->login();
    } else {
        // Otherwise, request an authorization code
        return $controller->authorize();
    }
});

// This is the GET route for the "register with ___" button
$app->get('/oauth/:provider/register', function ($provider) use ($app) {
    $controller = getProviderController($provider, 'register', $app);

    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "register";
    $get = $app->request->get();

    // If we received an authorization code, then resume our action
    if (isset($get['code'])) {
        // If OAuth call is successful and we have a code then 
        // show the updated registration page 
        $controller->pageRegister();
    } else {
        // Otherwise, request an authorization code
        return $controller->authorize();
    }
});

// This is the POST route that actually registers the user
$app->post('/oauth/:provider/register', function ($provider) use ($app) {
    $controller = getProviderController($provider, 'register', $app);
//    $controller = $_SESSION["userfrosting"]['oauth_controller'];

    // complete the registration process 
    $controller->register();
});

// This is the GET route for the "settings with ___" button
$app->get('/oauth/:provider/settings', function ($provider) use ($app) {
    $controller = getProviderController($provider, 'settings', $app);

    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "settings";
    $get = $app->request->get();

    // If we received an authorization code, then resume our action
    if (isset($get['code'])) {
    // If OAuth call is successful and we have a code then 
    // show the OAuth confirmation  
        $controller->pageConfirmOAuth();
    } else {
        // Otherwise, request an authorization code
        return $controller->authorize();
    }
});

// This is the POST route for the "settings/action with ___" button
$app->post('/oauth/:provider/settings/:action', function ($provider, $action) use ($app) {
    $controller = getProviderController($provider, 'settings', $app);
    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "settings-$action";
    // execute the action using the controller
    $controller->doOAuthAction($action);
});

// This is the GET route for the "settings/action with ___" button
$app->get('/oauth/:provider/settings/:action', function ($provider, $action) use ($app) {
    $controller = getProviderController($provider, 'settings', $app);
    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "settings-$action";
    // execute the action using the controller
    $controller->doOAuthAction($action);
});


// TODO: Register hooks for inserting buttons and other content into templates.  
// Will this be the same for all providers?
// 
// SN: we can call the class function in $controller to push the button, because each provider may have
// a different image or button type 

$app->hook('login.page.control', function () use ($app) {
    
}, 1);

$app->hook('settings.page.control', function () use ($app) {
    
}, 1);

$app->hook('register.page.control', function () use ($app) {
    
}, 1);

