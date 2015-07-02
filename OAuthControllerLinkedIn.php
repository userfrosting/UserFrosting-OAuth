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
    public function __construct($app,$callback_page='login') {
        parent::__construct($app);
        // TODO: these should be fetched from the site settings for this plugin (e.g. $app->site->get('oauth', 'client_id'); )
        $clientId = 'ID';
        $clientSecret = 'SECRET';
        $scopes = ['r_basicprofile', 'r_emailaddress'];

        $this->_provider = new \League\OAuth2\Client\Provider\LinkedIn([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $this->_app->site->uri['public'] . "/oauth/linkedin/$callback_page",
            'scopes' => $scopes,
        ]);
    }
   
    // This should be called when a user requests an authorization code by clicking a link (e.g. /oauth/linkedin/login)
    public function authorize(){ 
        $authUrl = $this->_provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->_provider->state;
        $this->_app->redirect($authUrl);    
    }
       
    // Log a user in by authenticating via LinkedIn.
    public function login(){
        // Authenticate 
        $user_details = $this->authenticate();
    
        // Load the OAuthUser object for the given uid
//        $oauth_user = OAuthUserLoader::fetch($user_details->uid, 'uid', 'linkedin');
        $oauth_user = OAuthUserLoader::fetch($user_details->uid, 'uid');
        
        // TODO: check that the user exists, and is not already logged in
        
        // Now get the UF user object and log the user in
        $_SESSION["userfrosting"]["user"] = \UserFrosting\UserLoader::fetch($oauth_user->user_id);
        $this->_app->user = $_SESSION["userfrosting"]["user"];
        $this->_app->user->login();
        $this->_app->redirect($this->_app->urlFor('uri_home'));
    }
    
    // Register a user by authenticating via LinkedIn.
    public function register(){
        // Authenticate OAuth
        $user_details_obj = $this->authenticate();
        // TODO: validate submitted fields and what-not

        // Create a new User object from the posted data
        $user = new \UserFrosting\User($data);
        
        // Save to database
        $user->store();
        
        // Create a new OAuthUser object
        $user_details = $this->transform($user_details_obj);
        $user_details['user_id'] = $user->id;
        $user_details['provider'] = "linkedin";
        
        $oauth_user = new OAuthUser($user_details);
        
        // Save to database
        $oauth_user->store();
    }
    
    public function pageRegister()
    {
        $user_details_obj = $this->authenticate();
        
        $this->_app->render('oauth_register.html', [
            'page' => [
                'author' =>         $this->_app->site->author,
                'title' =>          "OAuth Registration",
                'description' =>    "Registration using OAuth",
                'alerts' =>         $this->_app->alerts->getAndClearMessages()
            ],
                'oauth_details'=>$user_details_obj
        ]);                  
    }
    
    // This function should authenticate the user with LinkedIn and return the LinkedIn profile data for that user.
    private function authenticate(){
        $var_getarr = $this->_app->request->get();
        
        $ms = $this->_app->alerts;
        
        // Check given state against previously stored one to mitigate CSRF attack
        if (empty($var_getarr['state']) || ($var_getarr['state'] !== $_SESSION['oauth2state'])) {
            $this->app->alerts->addMessage('danger', 'Invalid or missing CSRF token.');
            unset($_SESSION['oauth2state']);
            $this->_app->halt(401);
        }      
        
        // Try to get an access token (using the authorization code grant)
        $token = $this->_provider->getAccessToken('authorization_code', [
            'code' => $var_getarr['code']
        ]);

        // We got an access token, so return the user's details
        $_SESSION['userfrosting']['oauth_details']=$this->_provider->getUserDetails($token);
        return $_SESSION['userfrosting']['oauth_details'];
    }
    
    // Transform raw details from the provider's API into the format necessary for our database
    private function transform($details_obj){
        $output_arr = [];
        
        $output_arr['uid'] =  $details_obj->uid; 
        $output_arr['oauth_details'] = serialize($details_obj);
        $output_arr['first_name'] =  $details_obj->firstName;
        $output_arr['last_name'] =  $details_obj->lastName;
        $output_arr['email'] =  $details_obj->email;
        $output_arr['picture_url'] =  $details_obj->imageUrl;
        
        return $output_arr;
    }
    
/**
 * register - to invoke the open auth plugin in the registration screen
 * @return oauth object 
 */
    public function registerButton()
    {
        // TODO
    }
/**
 * login - to invoke the open authentication plugin in the login screen
 * @return open authentication object
 */    
    public function loginButton()
    {
        // TODO
    }
/**
 * settings - to invoke the open authentication object in the user settings screen
 * @return open authentication object
 */    
    public function settingsFields()
    {
        // TODO
    }    
}