<?php
namespace UserFrosting\OAuth;

class OAuthUserLoader extends \UserFrosting\MySqlObjectLoader {

    protected static $_columns;     // A list of the allowed columns for this type of DB object. Must be set in the child concrete class.  DO NOT USE `id` as a column!
    protected static $_table;       // The name of the table whose rows this class represents. Must be set in the child concrete class.    
       
    public static function init(){
        // Set table and columns for this class.
        static::$_table = OAuthUser::getTableAuthUser();
        static::$_columns = static::$columns_user;
    }       

    /* Determine if a OAuthUser exists based on the value of a given column.  Returns true if a match is found, false otherwise.
     * @param value $value The value to find.
     * @param string $name The name of the column to match (defaults to id)
     * @return bool
    */
    public static function exists($value, $name = "id"){
        return parent::fetch($value, $name);
    }
   
    /* Fetch a single OAuthUser based on the value of a given column.  For non-unique columns, it will return the first entry found.  Returns false if no match is found.
     * @param value $value The value to find.
     * @param string $name The name of the column to match (defaults to id)
     * @return OAuthUser
    */
    public static function fetch($value, $name = "id"){
        $results = parent::fetch($value, $name);
        
        if ($results)
            return new OAuthUser($results, $results['id']);
        else
            return false;
    }
    
    /* Fetch a single OAuthUser based on the provider's ID.  Returns false if no match is found.
     * @param value $id The provider ID to find.
     * @param string $provider The name of the provider.
     * @return OAuthUser
    */
    public static function fetchByProviderId($id, $provider) {
        $db = static::connection();

        $auth_table = static::$_table;

        $sqlVars[':uid'] =  $id;
        $sqlVars[':provider'] =  $provider;

        $query = " SELECT a.*
            FROM `$auth_table` a
            WHERE a.uid = :uid
            AND provider = :provider";
        
        $stmt = $db->prepare($query);
        
        $stmt->execute($sqlVars);
          
        $results = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // PDO returns false if no record is found
        return new OAuthUser($results, $results['id']);
    }
    
    
    /* Fetch a list of OAuthUsers based on the value of a given column.  Returns empty array if no match is found.
     * @param value $value The value to find. (defaults to null, which means return all records in the table)
     * @param string $name The name of the column to match (defaults to null)
     * @return array An array of OAuthUser objects
    */
    public static function fetchAll($value = null, $name = null){
        $resultArr = parent::fetchAll($value, $name);
        
        $results = [];
        foreach ($resultArr as $id => $user)
            $results[$id] = new User($user, $id);

        return $results;
    }
}

?>
