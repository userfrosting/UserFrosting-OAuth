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
        $_SESSION['oauth2state'] = $this->_provider->state;
        $this->_app->redirect($authUrl);
    }

// 


/**
 * login - Log a user in by authenticating via OAuth Provider.
 */    
    public function login() {
// Authenticate 
        $user_details = $this->authenticate();
// Load the OAuthUser object for the given uid
        $oauth_user = OAuthUserLoader::fetch($user_details->uid, 'uid');
// TODO: check that the user exists, and is not already logged in
// Now get the UF user object and log the user in
        if ($oauth_user !== false) {
            $_SESSION["userfrosting"]["user"] = \UserFrosting\UserLoader::fetch($oauth_user->user_id);
            $this->_app->user = $_SESSION["userfrosting"]["user"];
            $this->_app->user->login();
        } else {
            $this->_app->alerts->addMessageTranslated("danger", "Your ".$this->_provider_name." Account is not connected to a local account. Plase register using LinkedIn first.", ["provider" => "LinkedIn"]);
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

/**
 * Perform OAuth action for the settings page
 * @param type $action
 */    
    public function doOAuthAction($action) {
        switch ($action) {
            case "confirm":
                $this->storeOAuth($this->_app->user->id);
                $this->_app->redirect('/account/settings');
                break;
        }
    }

/**
 * Register a user by authenticating via OAuth Provider.
 */    
    public function register() {
        $user = $this->ufRegister();
        $this->storeOAuth($user->id);
    }


/**
 * Show registration page using Open Authentication details    
 */    
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

/**
 * Show OAuth Confirmation page using Open Authentication details    
 */    
    public function pageConfirmOAuth() {
        $user_authobj = $this->authenticate();
        $user_details = $this->_user_profile;
// Waiting for league/oauth2-client to add $this->_provider->providerResponse attribute
//        $user_details_obj = $this->_provider->providerResponse;
//        $user_details = get_object_vars($user_details_obj);
        $get = $this->_app->request->get();
// If we received an authorization code, then resume our action
        if (isset($get['code'])) {

            $this->storeOAuth($this->_app->user->id);

// render the confirmation page, this html is stored locally in the plugin directory        
            
            $this->_app->render('oauth_confirm.html', [
                'page' => [
                    'author' => $this->_app->site->author,
                    'title' => $this->_provider_name." Confirmation",
                    'description' => $this->_provider_name." authentication successful",
                    'alerts' => $this->_app->alerts->getAndClearMessages()
                ],
                'oauth_details' => $this->_user_profile,
                'oauth_data' => $user_details,
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
//print_r($token);
//$token->expires
//$token->accessToken
//$token->uid
//$token->refreshToken
//die();
        
// We got an access token, so return the user's details
        $this->_user_profile = $this->_provider->getUserDetails($token);
// store the oauth details received from the call in a session variable for use later        
        $_SESSION['userfrosting']['oauth_details'] = $this->_user_profile;
        return $_SESSION['userfrosting']['oauth_details'];
    }


/**
 * Transform raw details from the provider's API into the format necessary for our database
 * @param type $details_obj
 * @return type
 */    
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
        if (!\UserFrosting\UserLoader::exists($this->_app->config('user_id_master'))){
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
        $data['user_name'] = strtolower(trim($data['user_name']));
        $data['display_name'] = trim($data['display_name']);
        $data['email'] = strtolower(trim($data['email']));
        $data['locale'] = $this->_app->site->default_locale;
        
        if ($this->_app->site->require_activation)
            $data['active'] = 0;
        else
            $data['active'] = 1;
        
        // Check if username or email already exists
        if (\UserFrosting\UserLoader::exists($data['user_name'], 'user_name')){
            $ms->addMessageTranslated("danger", "ACCOUNT_USERNAME_IN_USE", $data);
            $error = true;
        }

        if (\UserFrosting\UserLoader::exists($data['email'], 'email')){
            $ms->addMessageTranslated("danger", "ACCOUNT_EMAIL_IN_USE", $data);
            $error = true;
        }
        
        // Halt on any validation errors
        if ($error) {
            $this->_app->halt(400);
        }
    
        // Get default primary group (is_default = GROUP_DEFAULT_PRIMARY)
        $primaryGroup = \UserFrosting\GroupLoader::fetch(GROUP_DEFAULT_PRIMARY, "is_default");
        $data['primary_group_id'] = $primaryGroup->id;
        // Set default title for new users
        $data['title'] = $primaryGroup->new_user_title;
        // Hash password
        $data['password'] = \UserFrosting\Authentication::hashPassword($data['password']);
        
        // Create the user
        $user = new \UserFrosting\User($data);

        // Add user to default groups, including default primary group
        $defaultGroups = \UserFrosting\GroupLoader::fetchAll(GROUP_DEFAULT, "is_default");
        $user->addGroup($primaryGroup->id);
        foreach ($defaultGroups as $group_id => $group)
            $user->addGroup($group_id);    
        
        // Store new user to database
        $user->store();
        if ($this->_app->site->require_activation) {
            // Create and send activation email

            $mail = new \PHPMailer;
            
            $mail->From = $this->_app->site->admin_email;
            $mail->FromName = $this->_app->site->site_title;
            $mail->addAddress($user->email);     // Add a recipient
            $mail->addReplyTo($this->_app->site->admin_email, $this->_app->site->site_title);
            
            $mail->Subject = $this->_app->site->site_title . " - please activate your account";
            $mail->Body    = $this->_app->view()->render("common/mail/activate-new.html", [
                "user" => $user
            ]);
            
            $mail->isHTML(true);                                  // Set email format to HTML
            
            if(!$mail->send()) {
                $ms->addMessageTranslated("danger", "MAIL_ERROR");
                error_log('Mailer Error: ' . $mail->ErrorInfo);
/**
 *
 *  Srinivas : Should we be halting the registraiton process if the email could not be sent out
 * CAn we just give a message that a confirmation could not be sent out, contact the site admin
 * Because at this point the user record is already created. And the user should be able to login
 * Halting it here, does not let the process proceed with the OpenAuthentication, so now the user is stuck
 * with just UF account and does not have a link to the Open Authentication. 
 * Would be good to exit with a warning
 */                
//                 
//                $this->_app->halt(500);
            }

            // Activation required
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
