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

abstract class OAuthController extends \UserFrosting\BaseController {

    protected $_provider_name;       // An OAuth2 provider object
    protected $_provider;       // An OAuth2 provider object
    protected $_user_profile;       // An profile object returned by the provider
    

/**
 * constructor
 *
 * @param object $app app object.
 * @return none.
 */  
    public function __construct($app,$provider_name) {
        $this->_provider_name=$provider_name;
        parent::__construct($app);
        
    }

// This should be called when a user requests an authorization code by clicking a link (e.g. /oauth/:oauth/login)
    public function authorize() {
        $authUrl = $this->_provider->getAuthorizationUrl();
//        $_SESSION['oauth2state'] = $this->_provider->state;
        $this->_app->redirect($authUrl);
    }

// 


/**
 * login - Log a user in by authenticating via OAuth Provider.
 */    
    public function login() {
// Authenticate 
        $user_details = $this->authenticate();
//logarr($user_details,"Line 49");        
// Load the OAuthUser object for the given uid
        $oauth_user = OAuthUserLoader::fetch($user_details['id'], 'uid');

        // Get the alert message stream
        $ms = $this->_app->alerts; 
        
		
		
		// Now get the UF user object and log the user in
        if (isset($oauth_user->user_id)) {
            $var_ufuser = \UserFrosting\User::find($oauth_user->user_id);
			// Forward the user to their default page if he/she is already logged in
			if(!$this->_app->user->isGuest()) {
				$ms->addMessageTranslated("warning", "LOGIN_ALREADY_COMPLETE");
				$this->_app->redirect($this->_app->urlFor('uri_home'));
				//$this->_app->halt(403);
			}
			
			// Check that the user's account is enabled
			if ($var_ufuser->flag_enabled == 0){
				$ms->addMessageTranslated("danger", "ACCOUNT_DISABLED");
				$this->_app->redirect($this->_app->urlFor('uri_home'));
				//$this->_app->halt(403);
			}        
			
			// Check that the user's account is activated
			if ($var_ufuser->flag_verified == 0) {
				$ms->addMessageTranslated("danger", "ACCOUNT_INACTIVE");
				$this->_app->redirect($this->_app->urlFor('uri_home'));
				//$this->_app->halt(403);
			}
            
			//            $_SESSION["userfrosting"]["user"] = \UserFrosting\UserLoader::fetch($oauth_user->user_id);
			//            $this->_app->user = $_SESSION["userfrosting"]["user"];
			//            $this->_app->login($user);
            $this->_app->login($var_ufuser);       
            
			//           $this->_app->user->login();
        } else {
            $this->_app->alerts->addMessageTranslated("danger", "Your ".$this->_provider_name." Account is not connected to a local account. Plase register using ".$this->_provider_name." first.", ["provider" => $this->_provider_name]);
        }
        $this->_app->redirect($this->_app->urlFor('uri_home'));
    }

/**
 * storeOauth - Save the oauth data to the datbase
 * @param type $userid
 */    
    public function storeOAuth($userid) {
        $this->_user_profile = $_SESSION['userfrosting']['oauth_details'];
        $user_details = $this->transform($this->_user_profile);
        $user_details['user_id'] = $userid;
        $user_details['provider'] = strtolower($this->_provider_name);

// check to see if we have a record with this UID
        $cur_oauth_user = OAuthUserLoader::fetch($user_details['uid'], 'uid');
//logarr($user_details,"Line 82 user id is $userid and uid is ".$user_details['uid']);        
//logarr($cur_oauth_user,"Line 84");        
// if we find a record with the UID then, update the record 
        if (is_object($cur_oauth_user)) {
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
//logarr($user_details,"Line 99 user id $userid");      
//logarr($oauth_user,"Line 99 user id oauth_user");      
// Save to database
        $oauth_user->save();
    }

/**
 * Perform OAuth action for the settings page
 * @param type $action
 */    
    public function doOAuthAction($action) {
        switch ($action) {
            case "confirm":
                $this->storeOAuth($_SESSION["userfrosting"]["user_id"]);
                $this->_app->redirect($this->_app->site->uri['public'] . "/account/settings");  
                break;
        }
    }

/**
 * Register a user by authenticating via OAuth Provider.
 */    
    public function register() {

		error_log('try ufRegister');
        $user = $this->ufRegister();
		error_log('ufRegister done');
        $this->storeOAuth($user->id);
    }


/**
 * Show registration page using Open Authentication details    
 */    
    public function pageRegister() {
        $user_details_obj = $this->authenticate();
		//Test if this oauth provider's user is already associated with a user, log them in if so.
		$provider_uid = isset($user_details_obj['uid']) ? $user_details_obj['uid'] : (isset($user_details_obj['id']) ? $user_details_obj['id'] : "");
		if(OAuthUserLoader::exists($provider_uid, 'uid')){
            $this->_app->alerts->addMessage("danger", "You are already registered.");
			$this->_app->redirect($this->_app->site->uri['public'] . "/oauth/".strtolower($this->_provider_name)."/login"); //
		}
        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/register.json");
        $this->_app->jsValidator->setSchema($schema);       

        $settings = $this->_app->site;
        
        // If registration is disabled, send them back to the login page with an error message
        if (!$settings->can_register){
            $this->_app->alerts->addMessageTranslated("danger", "ACCOUNT_REGISTRATION_DISABLED");
            $this->_app->redirect('login');
        }
    
// render the registratoin page, this html is stored locally in the plugin directory        
        $this->_app->render('oauth_register.html.twig', [
            'page' => [
                'author' => $this->_app->site->author,
                'title' => "OAuth Registration",
                'description' => "Registration using OAuth",
                'alerts' => $this->_app->alerts->getAndClearMessages(),
                'active_page' => "account/register"
            ],
            'captcha_image' => $this->generateCaptcha(),
            'validators' => $this->_app->jsValidator->rules(),
            'oauth_details' => $this->transform($user_details_obj),
			'provider_name' => $this->_provider_name
        ]);
    }

/**
 * Show OAuth Confirmation page using Open Authentication details    
 */    
    public function pageConfirmOAuth() {
        
        $user_details_obj = $this->authenticate();
        $user_details = $this->_user_profile;

        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/register.json");
        $this->_app->jsValidator->setSchema($schema);       

        $settings = $this->_app->site;
        
        // If registration is disabled, send them back to the login page with an error message
        if (!$settings->can_register){
            $this->_app->alerts->addMessageTranslated("danger", "ACCOUNT_REGISTRATION_DISABLED");
            $this->_app->redirect('login');
        }
        
// Waiting for league/oauth2-client to add $this->_provider->providerResponse attribute
//        $user_details_obj = $this->_provider->providerResponse;
//        $user_details = get_object_vars($user_details_obj);
        $get = $this->_app->request->get();
// If we received an authorization code, then resume our action
        if (isset($get['code'])) {
//$_SESSION["userfrosting"]["user_id"]
//            $this->storeOAuth($this->_app->user->id);
//error_log("USer id in session is ".$_SESSION["userfrosting"]["user_id"]);            
            $this->storeOAuth($_SESSION["userfrosting"]["user_id"]);

// render the confirmation page, this html is stored locally in the plugin directory        
            
            $this->_app->render('oauth_confirm.html.twig', [
                'page' => [
                    'author' => $this->_app->site->author,
                    'title' => $this->_provider_name." Confirmation",
                    'description' => $this->_provider_name." authentication successful",
                    'alerts' => $this->_app->alerts->getAndClearMessages()
                ],
                'oauth_details' => $this->_user_profile,
                'oauth_data' => $this->transform($user_details),
                'oauth_provider' => $this->_provider_name
            ]);
        }
    }

/**
 * This function should authenticate the user with OAuth Provider and return the user profile data for that user.
 * @return type
 */
    private function authenticate() {
        $var_getarr = $this->_app->request->get();
        $ms = $this->_app->alerts;
// Try to get an access token (using the authorization code grant)
        $token = $this->_provider->getAccessToken('authorization_code', [
            'code' => $var_getarr['code']
        ]);
// We got an access token, so return the user's details
//        $this->_user_profile = $this->_provider->getUserDetails($token);
        $var_oauthuser = $this->_provider->getResourceOwner($token);
        $this->_user_profile = $var_oauthuser->toArray();
//logarr($var_oauthuser,"Line 209 oauth resourceowner");        
//logarr($this->_user_profile,"Line 209 oauth user profile");        
// store the oauth details received from the call in a session variable for use later        
        $_SESSION['userfrosting']['oauth_details'] = $this->_user_profile;
        return $_SESSION['userfrosting']['oauth_details'];
    }


/**
 * Transform raw details from the provider's API into the format necessary for our database
 * @param type $details_obj
 * @return type
 */    
    private function transform($oauth_arr) {
        $output_arr = [];

        $output_arr['uid'] = $oauth_arr['id'];
        $output_arr['oauth_details'] = serialize($oauth_arr);
        $output_arr['first_name'] = isset($oauth_arr['firstName']) ? $oauth_arr['firstName'] : (isset($oauth_arr['first_name']) ? $oauth_arr['first_name'] : "");
        $output_arr['last_name'] = isset($oauth_arr['lastName']) ? $oauth_arr['lastName'] : (isset($oauth_arr['last_name']) ? $oauth_arr['last_name'] : "");//$oauth_arr['lastName'];
        $output_arr['email'] = isset($oauth_arr['emailAddress']) ? $oauth_arr['emailAddress'] : (isset($oauth_arr['email']) ? $oauth_arr['email'] : "");//$oauth_arr['emailAddress'];
        $output_arr['picture_url'] = isset($oauth_arr['pictureUrl']) ? $oauth_arr['pictureUrl'] : (isset($oauth_arr['picture_url']) ? $oauth_arr['picture_url'] : "");//$oauth_arr['pictureUrl'];

        return $output_arr;
    }
    
/**
 * Process UserFrosting registration. This function is copied form UserFrosting class and modified to register the user first
 * and then save the Open Authentication details
 * @return \UserFrosting\User
 */    

    public function ufRegister(){
        // POST: user_name, display_name, email, title, password, passwordc, captcha, spiderbro, csrf_token
        $post = $this->_app->request->post();
        
        // Get the alert message stream
        $ms = $this->_app->alerts; 
        
        // Check the honeypot. 'spiderbro' is not a real field, it is hidden on the main page and must be submitted with its default value for this to be processed.
        if (!$post['spiderbro'] || $post['spiderbro'] != "http://"){
            error_log("Possible spam received:" . print_r($this->_app->request->post(), true));
            $ms->addMessage("danger", "Aww hellllls no!");
            $this->_app->halt(500);     // Don't let on about why the request failed ;-)
        }  
               
        // Load the request schema
        $requestSchema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/register.json");
                   
        // Set up Fortress to process the request
        $rf = new \Fortress\HTTPRequestFortress($ms, $requestSchema, $post);        

        // Security measure: do not allow registering new users until the master account has been created.        
        if (!\UserFrosting\User::find($this->_app->config('user_id_master'))){
            $ms->addMessageTranslated("danger", "MASTER_ACCOUNT_NOT_EXISTS");
            $this->_app->halt(403);
        }
          
        // Check if registration is currently enabled
        if (!$this->_app->site->can_register){
            $ms->addMessageTranslated("danger", "ACCOUNT_REGISTRATION_DISABLED");
            $this->_app->halt(403);
        }
          
        // Prevent the user from registering if he/she is already logged in
        if(!$this->_app->user->isGuest()) {
            $ms->addMessageTranslated("danger", "ACCOUNT_REGISTRATION_LOGOUT");
            $this->_app->halt(200);
        }
                
        // Sanitize data
        $rf->sanitize();
                
        // Validate, and halt on validation errors.
        $error = !$rf->validate(true);
        
        // Get the filtered data
        $data = $rf->data();        

        // Check captcha, if required
        if ($this->_app->site->enable_captcha == "1"){
            if (!$data['captcha'] || md5($data['captcha']) != $_SESSION['userfrosting']['captcha']){
                $ms->addMessageTranslated("danger", "CAPTCHA_FAIL");
                $error = true;
            }
        }
        
        // Remove captcha, password confirmation from object data
        $rf->removeFields(['captcha', 'passwordc']);
        
        // Perform desired data transformations.  Is this a feature we could add to Fortress?
        $data['display_name'] = trim($data['display_name']);
        $data['locale'] = $this->_app->site->default_locale;
        
        if ($this->_app->site->require_activation)
            $data['flag_verified'] = 0;
        else
            $data['flag_verified'] = 1;
        
        // Check if username or email already exists
        if (\UserFrosting\User::where('user_name', $data['user_name'])->first()){
            $ms->addMessageTranslated("danger", "ACCOUNT_USERNAME_IN_USE", $data);
            $error = true;
        }

        if (\UserFrosting\User::where('email', $data['email'])->first()){
            $ms->addMessageTranslated("danger", "ACCOUNT_EMAIL_IN_USE", $data);
            $error = true;
        }
        
        // Halt on any validation errors
        if ($error) {
            $this->_app->halt(400);
        }
    
        // Get default primary group (is_default = GROUP_DEFAULT_PRIMARY)
        $primaryGroup = \UserFrosting\Group::where('is_default', GROUP_DEFAULT_PRIMARY)->first();
        
        // Check that a default primary group is actually set
        if (!$primaryGroup){
            $ms->addMessageTranslated("danger", "ACCOUNT_REGISTRATION_BROKEN");
            error_log("Account registration is not working because a default primary group has not been set.");
            $this->_app->halt(500);
        }

        $data['primary_group_id'] = $primaryGroup->id;
        // Set default title for new users
        $data['title'] = $primaryGroup->new_user_title;
        // Hash password
        $data['password'] = \UserFrosting\Authentication::hashPassword($data['password']);
        
        // Create the user
        $user = new \UserFrosting\User($data);

        // Add user to default groups, including default primary group
        $defaultGroups = \UserFrosting\Group::where('is_default', GROUP_DEFAULT)->get();
        $user->addGroup($primaryGroup->id);
        foreach ($defaultGroups as $group)
            $user->addGroup($group->id);    
        
        // Create sign-up event
        $user->newEventSignUp();
        
        // Store new user to database
        $user->save();
        
        if ($this->_app->site->require_activation) {
            // Create verification request event
            $user->newEventVerificationRequest();
            $user->save();      // Re-save with verification event      
            
            // Create and send verification email
            $twig = $this->_app->view()->getEnvironment();
            $template = $twig->loadTemplate("mail/activate-new.twig");        
            $notification = new \UserFrosting\Notification($template);
            $notification->fromWebsite();      // Automatically sets sender and reply-to
            $notification->addEmailRecipient($user->email, $user->display_name, [
                "user" => $user
            ]);
            
            try {
                $notification->send();
            } catch (\phpmailerException $e){
                $ms->addMessageTranslated("danger", "MAIL_ERROR");
                error_log('Mailer Error: ' . $e->errorMessage());
                //$this->_app->halt(500);
            }
            
            $ms->addMessageTranslated("success", "ACCOUNT_REGISTRATION_COMPLETE_TYPE2");
        } else
            // No activation required
            $ms->addMessageTranslated("success", "ACCOUNT_REGISTRATION_COMPLETE_TYPE1");
// Srinivas : The OAuth function will need the user object, so that it can get the ID to save the OAuth record
// Invoking this in OAuth to register using         
        return $user;
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
