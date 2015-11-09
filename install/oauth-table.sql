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
);