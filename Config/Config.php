<?php
/**
 * Noze Framework for Xat Bot
 * Copyright © 2014, Emeric Fèvre
 * 
 * Noze is a framework written in PHP to allow fast creation of xat bots. 
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Noze
 * @author Emeric Fèvre https://twitter.com/NozeAres <zoro.fmt@gmail.com>
 * @version 0.1
 * @copyright Copyright © 2014, Emeric Fèvre
 *
 * @filename Config.Config.php
 */


$configuration = array (

	/**
	 * Bot configuration
	 */
	'bot' => array(
		'username'		=> 'BotNozeAI',
		'password'		=> '',
		'name'          => 'BotNoze',
		'avatar'        => '624',
		'home'          => '',

		/**
		 * Admins of the bot
		 */
		'admin' 		=> array(
			'1000069',
			'1520000'
		)
	),

	/**
	 * Room configuration
	 */
	'room' => array(
		/*
		 * The bot will automatically join this room
		 */
		'name'      => 'Noze',
		'id'        => '',

		/**
		 * The number of the pool that the bot must enter, the bot must have the required rank to enter in staff and banned pool
		 *  - 0 : Normal (default)
		 *  - 1 : Staff pool
		 *  - 2 : Banned pool
		 */
		//'pool'      => 1,

		/**
		 * Password of the chat room, to make the bot main owner directly
		 */
		'password'  => ''

	),

	/**
	 * Modules configuration
	 */
	'modules.priority' => array(),

	/**
	 * Commands configuration
	 */
	'commands' => array(
		'prefix' => '!'
	),

	/**
	 * Debug configuration
	 *  Debug messages are displayed like :
	 *      [DEBUG] In C:\wamp\www\BotNoze\Noze.php :
     *
	 *      Line  Function     Message
	 *      93    __construct  That's a debug message.
	 */
	'debug' => array(
		/**
		 * If True, all debug messages will be displayed
		 */
		'activate' => true,
		/**
		 * This message will be displayed at the beginning in each debug's message
		 */
		'intro' => '[DEBUG]'
	)

);