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

    protected $_provider;       // An OAuth2 provider object
    protected $_user_profile;       // An profile object returned by the provider

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
// Srinivas : Should we be halting the registraiton process if the email could not be sent out
// CAn we just give a message that a confirmation could not be sent out, contact the site admin
// Because at this point the user record is already created. And the user should be able to login
// Halting it here, does not let the process proceed with the OpenAuthentication, so now the user is stuck
// with just UF account and does not have a link to the Open Authentication. 
// Would be good to exit with a warning
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
