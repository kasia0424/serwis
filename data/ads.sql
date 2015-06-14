/* OG£OSZENIA */
CREATE TABLE IF NOT EXISTS `so_ads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(64) COLLATE utf8_bin NOT NULL,
  `text` char(255) COLLATE utf8_bin NOT NULL,
  `postDate` DATETIME NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- INSERT INTO `so_ads` (`id`, `title`, `text`, `category_id`, `postDate`, `photo_id`, `user_id`) VALUES
-- (1, 'Michael Jackson', 'Thriller'),
-- (2, 'Pink Floyd', 'The Dark Side of the Moon'),
-- (3, 'Whitney Houston / Various artists', 'The Bodyguard');
INSERT INTO `so_ads` (`id`, `title`, `text`, `postDate`, `category_id`, `user_id`) VALUES
	(1, 'tytu³ jakiœ tam', 'cos oddam, czegoœ innego nie. Dzwoñcie!', '2015-02-10 23:10:02', 1, 1);


/* KATEGORIE */
CREATE TABLE IF NOT EXISTS `so_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(128) COLLATE utf8_bin NOT NULL,
  `description` char(128) COLLATE utf8_bin NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `so_categories` (`id`, `name`, `description`) VALUES
 (1, 'kupiê', 'kupiê coœ');


/* ZDJÊCIA */
CREATE TABLE IF NOT EXISTS `so_photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(128) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


/* USER DETAILS */
CREATE TABLE IF NOT EXISTS `so_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phone_number` int(20) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


/* AUTORYZACJA */
CREATE TABLE IF NOT EXISTS `so_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(32) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `so_roles` (`id`, `name`) VALUES
(1, 'ROLE_ADMIN'),
(2, 'ROLE_USER');

CREATE TABLE IF NOT EXISTS `so_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` char(32) COLLATE utf8_bin NOT NULL,
  `password` char(128) COLLATE utf8_bin NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_users_1` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- l: TestAdmin, p: jTE7cm666Xk6
-- INSERT INTO `so_users` (`id`, `login`, `password`, `role_id`) VALUES ('1', 'TestAdmin', 'DJAhPVmfV76bEZ9xsW5O3oaN9o+zmwpRZ78XW5QspToIjtbBlAFSbd5v3l/QFdj1F5svzjMZ5tuQsugny0MnpA==', '1');
-- l: TestUser, p: qAWfMuqyVEe5
-- INSERT INTO `so_users` (`id`, `login`, `password`, `role_id`) VALUES ('2', 'TestUser', '31sJZ7dGw9iFvJUqKIuS34JHj3D0MPLplLN+dxTq3vL3zz8pxkUSUCamau8UW1nGBOyNlQ0NE1NLWXYZNSV/Hg==', '2');


/* klucze obce */
ALTER TABLE `so_users`
  ADD CONSTRAINT `FK_users_1` FOREIGN KEY (`role_id`) REFERENCES `so_roles` (`id`);

ALTER TABLE `so_details`
  ADD CONSTRAINT `FK_details_1` FOREIGN KEY (`user_id`) REFERENCES `so_users` (`id`);

ALTER TABLE `so_ads`
  ADD CONSTRAINT `FK_ads_1` FOREIGN KEY (`category_id`) REFERENCES `so_categories` (`id`);

ALTER TABLE  `so_ads` ADD  `photo_id` INT UNSIGNED NULL AFTER  `id` ;
ALTER TABLE  `so_ads` ADD CONSTRAINT  `FK_ads_3` FOREIGN KEY (  `photo_id` ) REFERENCES  `so_photos` (  `id` );

ALTER TABLE `so_ads`
  ADD CONSTRAINT `FK_ads_2` FOREIGN KEY (`user_id`) REFERENCES `so_users` (`id`);