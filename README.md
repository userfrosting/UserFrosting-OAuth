# UserFrosting-OAuth v0.1

OAuth Plugin for UserFrosting

http://www.userfrosting.com


## Installation

Add these lines to the composer.json in the userfrosting directory

        "league/oauth2-facebook": "*",
        "league/oauth2-linkedin":"*",
        "league/oauth2-google":"*",
        "league/oauth2-instagram":"*",

## Features

- Utilizes composer based packages and a plugin wrapper for UserFrosting.
- Ability to connect more than one Open Auth provider to one User Frosting login account

## Installation and Activation

- Download the zip file and put this in the userfrosting/plugins folder
- Create OAuth Table using the following script
CREATE TABLE `uf_user_oauth` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `first_name` varchar(200) DEFAULT NULL,
  `last_name` varchar(200) DEFAULT NULL,
  `picture_url` varchar(500) DEFAULT NULL,
  `oauth_details` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
- Update the UserFrosting-OAuth/controllers/OAuthControllerFacebook.php / Linkedin.php with the key and secret values for your application
- Login using oauth
      /oauth/linkedin/login
- Register using OAuth
    /oauth/linkedin/register
- Add OAuth to existing user 
    /oauth/linkedin/settings