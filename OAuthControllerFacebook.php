<?php

namespace UserFrosting\OAuth;

/**
 * OAuthControllerFacebook
 *
 * Controller class for authenticating via Facebook.
 *
 * @package UserFrosting-OpenAuthentication
 * @author Srinivas Nukala
 * @link http://srinivasnukala.com
 */
class OAuthControllerFacebook extends OAuthController {

    /**
     * constructor
     *
     * @param object $app app object.
     * @return none.
     */
    public function __construct($app, $callback_page = 'login') {
        parent::__construct($app,'Facebook');
// TODO: these should be fetched from the site settings for this plugin (e.g. $app->site->get('oauth', 'client_id'); )
        $clientId = 'ID';
        $clientSecret = 'SECRET';

        $this->_provider = new \League\OAuth2\Client\Provider\Facebook([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $this->_app->site->uri['public'] . "/oauth/facebook/$callback_page"]);
//        $this->_provider->fields = $oaFields;
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
