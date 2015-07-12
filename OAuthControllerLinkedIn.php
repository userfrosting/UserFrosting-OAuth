<?php

namespace UserFrosting\OAuth;

/**
 * OAuthControllerLinkedIn
 *
 * Controller class for authenticating via LinkedIn.
 *
 * @package UserFrosting-OpenAuthentication
 * @author Srinivas Nukala
 * @link http://srinivasnukala.com
 */
class OAuthControllerLinkedIn extends OAuthController {

    /**
     * constructor
     *
     * @param object $app app object.
     * @return none.
     */
    public function __construct($app, $callback_page = 'login') {
        parent::__construct($app);
// TODO: these should be fetched from the site settings for this plugin (e.g. $app->site->get('oauth', 'client_id'); )
        $clientId = 'CLIENT ID';
        $clientSecret = 'SECRET';
        $scopes = ['r_basicprofile', 'r_emailaddress'];
        $oaFields = ['id', 'email-address', 'first-name', 'last-name', 'headline',
            'location', 'industry', 'picture-url', 'public-profile-url', 'summary', 'specialties', 'positions'];

        $this->_provider = new \League\OAuth2\Client\Provider\LinkedIn([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $this->_app->site->uri['public'] . "/oauth/linkedin/$callback_page",
            'scopes' => $scopes]);
        $this->_provider->fields = $oaFields;
    }

// This should be called when a user requests an authorization code by clicking a link (e.g. /oauth/linkedin/login)
    public function authorize() {
        $authUrl = $this->_provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->_provider->state;
        $this->_app->redirect($authUrl);
    }

// Log a user in by authenticating via LinkedIn.
    public function login() {
// Authenticate 
        $user_details = $this->authenticate();
// Load the OAuthUser object for the given uid
        $oauth_user = OAuthUserLoader::fetch($user_details->uid, 'uid');
//print_r($oauth_user);        
// TODO: check that the user exists, and is not already logged in
// Now get the UF user object and log the user in
        if ($oauth_user !== false) {
            $_SESSION["userfrosting"]["user"] = \UserFrosting\UserLoader::fetch($oauth_user->user_id);
            $this->_app->user = $_SESSION["userfrosting"]["user"];
            $this->_app->user->login();
        } else {
            $this->_app->alerts->addMessageTranslated("danger", "Your LinkedIn Account is not connected to a local account. Plase register using LinkedIn first.", ["provider" => "LinkedIn"]);
        }
        $this->_app->redirect($this->_app->urlFor('uri_home'));
    }

    public function storeOAuth($userid) {
        $this->_user_profile = $_SESSION['userfrosting']['oauth_details'];
        $user_details = $this->transform($this->_user_profile);
        $user_details['user_id'] = $userid;
        $user_details['provider'] = "linkedin";

// check to see if we have a record with this UID
        $cur_oauth_user = OAuthUserLoader::fetch($user_details['uid'], 'uid');
// if we find a record with the UID then, update the record 
        if ($cur_oauth_user !== false) {
            foreach($user_details as $usrkey=>$usrdata)
            {
// do not update the UID or user_id fields                
                if($usrkey !='user_id' && $usrkey !='uid')
                $cur_oauth_user->$usrkey =$usrdata;
            }
            $oauth_user = $cur_oauth_user;
            
        }
// the UID does not exist so create new
        else
        {
            $oauth_user = new OAuthUser($user_details);
        }
        
// Save to database
        $oauth_user->store();
    }

    public function doOAuthAction($action) {
        switch ($action) {
            case "confirm":
                $this->storeOAuth($this->_app->user->id);
                $this->_app->redirect('/account/settings');
                break;
        }
    }

// Register a user by authenticating via LinkedIn.
    public function register() {
        $user = $this->ufRegister();
        $this->storeOAuth($user->id);
    }

// Show registration page using Open Authentication details    
    public function pageRegister() {
        $user_details_obj = $this->authenticate();

        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/register.json");
        $validators = new \Fortress\ClientSideValidator($schema, $this->_app->translator);

        $settings = $this->_app->site;

// If registration is disabled, send them back to the home page with an error message
        if (!$settings->can_register) {
            $this->_app->alerts->addMessageTranslated("danger", "ACCOUNT_REGISTRATION_DISABLED");
            $this->_app->redirect('login');
        }

// render the registratoin page, this html is stored locally in the plugin directory        
        $this->_app->render('oauth_register.html', [
            'page' => [
                'author' => $this->_app->site->author,
                'title' => "OAuth Registration",
                'description' => "Registration using OAuth",
                'alerts' => $this->_app->alerts->getAndClearMessages(),
                'active_page' => "account/register"
            ],
            'captcha_image' => $this->generateCaptcha(),
            'validators' => $validators->formValidationRulesJson(),
            'oauth_details' => $user_details_obj
        ]);
    }

// Show OAuth Confirmation page using Open Authentication details    
    
    public function pageConfirmOAuth() {
        $user_details = $this->authenticate();
        $user_details_obj = $this->_provider->providerResponse;
        $get = $this->_app->request->get();
// If we received an authorization code, then resume our action
        if (isset($get['code'])) {

            $this->storeOAuth($this->_app->user->id);

// render the confirmation page, this html is stored locally in the plugin directory        
            
            $this->_app->render('oauth_confirm.html', [
                'page' => [
                    'author' => $this->_app->site->author,
                    'title' => "LinkedIn Confirmation",
                    'description' => "LinkedIn authentication successful",
                    'alerts' => $this->_app->alerts->getAndClearMessages()
                ],
                'oauth_details' => $this->_user_profile,
                'oauth_data' => get_object_vars($user_details_obj),
                'oauth_provider' => 'LinkedIn'
            ]);
        }
    }

// This function should authenticate the user with LinkedIn and return the LinkedIn profile data for that user.
    private function authenticate() {
        $var_getarr = $this->_app->request->get();
        $ms = $this->_app->alerts;
// Try to get an access token (using the authorization code grant)
        $token = $this->_provider->getAccessToken('authorization_code', [
            'code' => $var_getarr['code']
        ]);

// We got an access token, so return the user's details
        $this->_user_profile = $this->_provider->getUserDetails($token);
// store the oauth details received from the call in a session variable for use later        
        $_SESSION['userfrosting']['oauth_details'] = $this->_user_profile;
        return $_SESSION['userfrosting']['oauth_details'];
    }

// Transform raw details from the provider's API into the format necessary for our database
    private function transform($details_obj) {
        $output_arr = [];

        $output_arr['uid'] = $details_obj->uid;
        $output_arr['oauth_details'] = serialize($details_obj);
        $output_arr['first_name'] = $details_obj->firstName;
        $output_arr['last_name'] = $details_obj->lastName;
        $output_arr['email'] = $details_obj->email;
        $output_arr['picture_url'] = $details_obj->imageUrl;

        return $output_arr;
    }

    /**
     * register - to invoke the open auth plugin in the registration screen
     * @return oauth object 
     */
    public function registerButton() {
// TODO
    }

    /**
     * login - to invoke the open authentication plugin in the login screen
     * @return open authentication object
     */
    public function loginButton() {
// TODO
    }

    /**
     * settings - to invoke the open authentication object in the user settings screen
     * @return open authentication object
     */
    public function settingsFields() {
// TODO
    }

}
