<?php
/*
 *
 * OGP - Open Game Panel
 * Copyright (C) 2008 - 2017 The OGP Development Team
 *
 * http://www.opengamepanel.org/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$module_title = "Tickets";
$module_version = "0.1";
$db_version = 0;
$module_required = false;
$module_menus = array(
					array(
						'name'	=>	'Support Tickets',
						'group'	=>	'user'
					)
				);

$install_queries[0] = array(
	"DROP TABLE IF EXISTS `".OGP_DB_PREFIX."ticket_replies`",
	"DROP TABLE IF EXISTS `".OGP_DB_PREFIX."tickets`",

	"CREATE TABLE `".OGP_DB_PREFIX."tickets` (
		`tid` int NOT NULL AUTO_INCREMENT,
		`uid` varchar(32) NOT NULL UNIQUE,
		`user_id` int NOT NULL,
		`parent_id` int NOT NULL,
		`user_ip` varbinary(16) NOT NULL,
		`subject` varchar(64) NOT NULL,
		`message` TEXT NOT NULL,
		`service_id` int,
		`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
		`last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP,
		`status` tinyint NOT NULL,
		`assigned_to` tinyint,
		PRIMARY KEY (`tid`)
	);",

	"CREATE TABLE `".OGP_DB_PREFIX."ticket_replies` (
		`reply_id` int NOT NULL AUTO_INCREMENT,
		`ticket_id` int NOT NULL,
		`user_id` int NOT NULL,
		`user_ip` varbinary(16) NOT NULL,
		`message` TEXT NOT NULL,
		`date` DATETIME DEFAULT CURRENT_TIMESTAMP,
		`rating` tinyint DEFAULT '0',
		`is_admin` int DEFAULT '0',
		PRIMARY KEY (`reply_id`)
	);",

	"ALTER TABLE `".OGP_DB_PREFIX."ticket_replies` ADD CONSTRAINT `".OGP_DB_PREFIX."ticket_replies_fk0` FOREIGN KEY (`ticket_id`) REFERENCES `".OGP_DB_PREFIX."tickets`(`tid`);",
);