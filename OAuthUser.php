<?php
namespace UserFrosting\OAuth;

class OAuthUser extends \UserFrosting\MySqlDatabaseObject {
        
    protected static $table_auth='user_oauth';  // The oauth table. 
    protected static $columns_user_auth = [
            "provider",
            "user_id",
            "uid",
            "email",
            "first_name",
            "last_name",
            "picture_url",
            "oauth_details",
        "created_at"];
    
    public function __construct($properties, $id = null) {
        $this->_table = static::getTableAuthUser();
        $this->_columns = static::$columns_user_auth;
                            
        parent::__construct($properties, $id);
            
    }
    
    public static function getTableAuthUser(){
        return static::$app->config('db')['db_prefix'] . static::$table_auth;
    }
    
    public static function getColumnsAuthUser(){
        return $this->_columns;
    }
    
/**
 * store : Function to save OAuth records into database
 */        
    public function store($force_create = false){
        // Initialize creation time for new OAuthUser records
        if (!isset($this->_id) || $force_create){
            $this->created_at = date("Y-m-d H:i:s");
        }
        
        // Update the record
        parent::store();
    }
    
/**
 * showOAuthDetails - create a HTML display string to show name and picture from the provider
 * this is just an example useing Linked in the object property names may change based on your provider
 * @return string
 */    
    public function showOAuthDetails() {
//        print_r($this->_oauthData);
//        die("Line 113");
        if (is_object($this->_oauthData)) {
            $var_retstr = '<h2>Hello ' . $this->_oauthData->name . '</h2>' .
                    '<img src="' . $this->_oauthData->imageUrl . '">';
            $gravatar_link = 'http://www.gravatar.com/avatar/' . md5($this->_oauthData->email) . '?s=32';
            $var_retstr .= '<img src="' . $gravatar_link . '" />';
        } else
            $var_retstr = '';
        
        $this->$this->_html['oauthHTML']=$var_retstr;
        return $var_retstr;
    }

/**
 * showOAuthModelDetails - create a HTML display string using the multiple open authentication
 * rows stored in the database to show name and picture and other details stored in the database
 * @return string
 */    
    
    public function showOAuthModelDetails() {

        $this->_oauthData = $this->_oauthUser->fetchOauth($this->_app->user->id,'user_id');
        $var_retstr = '';        
//        print_r($this->_oauthData);
//        die("Line 113");
        foreach($this->_oauthData as $var_oauth) 
            {
            $var_retstr .= '<h2>Hello ' . $var_oauth['first_name'] .' '.$var_oauth['last_name']. '</h2>' .
                    '<img src="' . $var_oauth['picture_url'] . '">';
            $gravatar_link = 'http://www.gravatar.com/avatar/' . md5($var_oauth['email']) . '?s=32';
            $var_retstr .= '<img src="' . $gravatar_link . '" />';
        } 
        
        $this->_html['oauthModelHTML']=$var_retstr;
        
        return $var_retstr;
    }    
    
}