<?php
namespace UserFrosting\OAuth;

/**
 * OAuthController
 *
 * An abstract controller class for connecting to OAuth2 providers.
 *
 * @package UserFrosting-OpenAuthentication
 * @author Srinivas Nukala
 * @link http://srinivasnukala.com
 */

abstract class OAuthController extends UserFrosting\BaseController {

    protected $_provider;       // An OAuth2 provider object

/**
 * constructor
 *
 * @param object $app app object.
 * @return none.
 */  
    public function __construct($app) {
        parent::__construct($app);
    }


    // This should be called when a user requests an authorization code by clicking a link (e.g. /oauth/linkedin/login)
    public function authorize(){

    }
    
    
    // This function should automatically login a user who has been authenticated by the OAuth provider
    public function login(){
    
    }
    
    // Transform raw details from the provider's API into the format necessary for our database
    private function transform($details_obj) {
    
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
