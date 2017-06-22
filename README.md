# Works with UserFrosting v 3.x 
This plugin does not work with UF 4.x. This will be built as a UF feature in near future.

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

```
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
PRIMARY KEY (`id`))
```

## For Facebook
- Update the file UserFrosting-OAuth/controllers/OAuthControllerFacebook.php 
with Key and Secret from LinkedIn Registered application

# Login using Facebook
      http://your_uf_application.com/oauth/facebook/login
# Register using Facebook 
    http://your_uf_application.com/oauth/facebook/register
    
## Add OAuth for Facebook to existing user 
This plugin also provides a way to add OAuth token to a current user. 
 - To do this login normally into UserFrosting application
 - go to http://your_uf_application.com/oauth/facebook/settings
    

# For LinkedIn
- Update the file UserFrosting-OAuth/controllers/OAuthControllerLinkedin.php 
with Key and Secret from LinkedIn Registered application

## Login using LinkedIn
      http://your_uf_application.com/oauth/linkedin/login
## Register using LinkedIn 
    http://your_uf_application.com/oauth/linkedin/register
## Add OAuth for LinkedIn to existing user 
This plugin also provides a way to add OAuth token to a current user. 
 - To do this login normally into UserFrosting application
 - go to http://your_uf_application.com/oauth/linkedin/settings
    

# Having issues getting this to work ??

if you have latest versions of league/oauth2-client which also needs guzzlehttp/guzzle (6.1.1) then you will have the right packages for this to work. 
One of my projects had a dependency for guzzle(<6.0) so it did not get the latest version of the league/oauth2-client and this plugin did not work there. 

But i just installed UF with the 
- league/oauth2-linkedin (0.4.0)
- set the key and secret in the controller/OAuthControllerLinkedIn.php
- setup the Linkedin app (developer.linkedin.com) to redirect to 
     - http://yourapplicaiton.com/oauth/linkedin/login (Linked in Login)
     - http://yourapplicaiton.com/oauth/linkedin/register (Register using LinkedIn)
     - http://yourapplicaiton.com/oauth/linkedin/settings (Add LinkedIn login to existing user)

the plugin uses all threeurls for 3 different use cases

Please drop me a note if you have any questions
