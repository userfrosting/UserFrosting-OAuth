<?php

namespace UserFrosting\OAuth;

// TODO: Let Composer autoload all our classes

use UserFrosting as UF;


// Fetch the relevant controller
function getProviderController($provider){
    switch ($provider) {
        case "linkedin" : return new UF\OAuth\OAuthControllerLinkedIn($app);
        
        default:          $app->notFound();
    }
}



// Define routes

// This is the route that the provider will use as its redirect_uri
$app->get('/oauth/:provider', function ($provider) use ($app) {
    // The provider has sent us back here.  Did we get an authorization code, or the access token?
    $get = $app->request->get();
    
    // If we received an authorization code, then resume our action
    if (isset($get['code'])) {
        $controller = getProviderController($provider);
        // If we're logging them in, just call that method and it will automatically redirect us
        if ($_SESSION['oauth_action'] == "login"){
            $controller->login();
        } else if ($_SESSION['oauth_action'] == "register"){
            // TODO: take them to the registration confirmation page
        
        }
    } else {
        // Otherwise, request an authorization code
        return $controller->authorize();
    }
});

// This is the GET route for the "login with ___" button
$app->get('/oauth/:provider/login', function ($provider) use ($app) {
    $controller = getProviderController($provider);

    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "login";
    
    // Get the authorization code
    return $controller->authorize();
});

// This is the GET route for the "register with ___" button
$app->get('/oauth/:provider/register', function ($provider) use ($app) {
    $controller = getProviderController($provider);

    // Store this action so we remember what we're doing after we get the authorization code
    $_SESSION['oauth_action'] = "register";    
    
    // Get the authorization code
    return $controller->authorize();
});

// This is the POST route that actually registers the user
$app->post('/oauth/:provider/register', function ($provider) use ($app) {
    $controller = getProviderController($provider);

    $controller->register();
});

// TODO: Register hooks for inserting buttons and other content into templates.  Will this be the same for all providers?
$app->hook('login.page.control', function () use ($app) {

}, 1);

$app->hook('settings.page.control', function () use ($app) {

}, 1);

$app->hook('register.page.control', function () use ($app) {

}, 1);

