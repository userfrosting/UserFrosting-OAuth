<?php

namespace UserFrosting\OAuth;

require_once('controllers/OAuthController.php');
require_once('models/OAuthUser.php');
require_once('models/OAuthUserLoader.php');

// TODO: Let Composer autoload all our classes

use UserFrosting as UF;

function echobr($par_str) {
    echo("<br>$par_str<br>");
    error_log("$par_str \n");
}

function echoarr($par_arr, $par_comment = 'none') {
    if ($par_comment != 'none')
        echobr($par_comment);
    echo "<pre>";
    print_r($par_arr);
    echo "</pre>";
    error_log("<pre>$par_comment \n" .
            print_r($par_arr, true) . " \n\n </pre>");
}

function logarr($par_arr, $par_comment = 'none') {
    error_log("<pre>$par_comment \n" .
            print_r($par_arr, true) . " \n\n </pre>");
}

// Fetch the relevant controller
function getProviderController($provider, $callback_page, $app) {
    switch ($provider) {
        case "linkedin" :
            require_once('controllers/OAuthControllerLinkedIn.php');
            return new \UserFrosting\OAuth\OAuthControllerLinkedIn($app, $callback_page);
            break;
        case "facebook" :
            require_once('controllers/OAuthControllerFacebook.php');
            return new \UserFrosting\OAuth\OAuthControllerFacebook($app, $callback_page);
            break;
        default:
//            return false;
            $app->notFound();
            break;
    }
}

/* Import UserFrosting variables as global Twig variables */
$twig = $app->view()->getEnvironment();

$twig->addFilter(new \Twig_SimpleFilter('cast_to_array', function ($stdClassObject) {
    $response = array();
    foreach ((array) $stdClassObject as $key => $value) {
        $response[str_replace('*', '', $key)] = $value;
    }
//print_r($response);    
    return $response;
}));

$loader = $twig->getLoader();
// First look in user's theme...
$loader->addPath($app->config('plugins.path') . "/UserFrosting-OAuth/templates");

$table_user_oauth = new \UserFrosting\DatabaseTable($app->config('db')['db_prefix'] . "user_oauth", [
    "provider",
    "user_id",
    "uid",
    "email",
    "first_name",
    "last_name",
    "picture_url",
    "oauth_details",
    "created_at"]);

// Innitialize the OAuth User Loader the table and column definitions will be loaded
//\UserFrosting\Database::setSchemaTable("staff_event_user", $table_staff_event_user);
//OAuthUserLoader::init($table_user_oauth);
\UserFrosting\Database::setSchemaTable("user_oauth", $table_user_oauth);

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
//http://userfrosting.github/oauth/linkedin/settings/confirm
$app->post('/oauth/:provider/settings/:action', function ($provider, $action) use ($app) {
error_log("Line 150 $provider ");
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

