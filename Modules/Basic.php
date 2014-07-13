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
 * @filename Modules.Basic.php
 */

namespace Noze\Modules;
use \Noze;
use \Noze\Configuration;
use \Noze\Utility\Debug;
use \Noze\Utility\Request;

class Basic implements \Noze\Module\ModuleInterface {

	/**
	 * Commands configuration
	 *
	 * @const array
	 */
	private $ConfigCommands = array();

	/**
	 * List of basic commands
	 *
	 * @var array
	 */
	private $Commands = array();



	/**
	 * Constructor, set up the configuration.
	 */
	public function __construct() {

		$this->ConfigCommands   = Configuration::getConfig('commands');

		/**
		 * List of commands and arguments needed. You must put the name of the command in lowercase.
		 */
		$Commands = array (
			'say'       => array (
				'params' => 1,
				'syntax' => $this->ConfigCommands['prefix'] . 'Say [Message]'
			),
			'info'   => array(
				'params'    => 0,
				'syntax'    => $this->ConfigCommands['prefix'] . 'Info'
			)
		);

		$this->Commands = $Commands;

	}


	/**
	 * Called when a message is posted in the chat.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param object    $Message
	 *
	 * @return bool
	 */
	public function onChannelMessage(Noze\Noze $Noze, $Events, $Message) {

		/**
		 * Put the command and all the arguments in lowercase.
		 */
		$Message->Command = strtolower($Message->Command);
		$Message->Arguments = array_map('strtolower', $Message->Arguments);

		/**
		 * We check if we have the commandCode, and if the command exist,
		 * else it's just a simple phrase or an admin command and we do nothing here. :)
		 */
		if($Message->CommandCode != $this->ConfigCommands['prefix']) {

			return true;
		} elseif(!isset($this->Commands[$Message->Command])) {

			return true;
		}

		/**
		 * Do the handles commands
		 */
		$this->handleQuery($Noze, $Events, $Message);

		return true;
	}


	/**
	 * Handles commands.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param object    $Message
	 */
	private function handleQuery(Noze\Noze $Noze, $Events, $Message) {

		/**
		 * Verify required information.
		 */
		if(count($Message->Arguments) < $this->Commands[$Message->Command]['params']) {
			//Check if the user has given enough parameters.
			$Events->message('Not enough parameters given. Syntax: ' . $this->Commands[$Message->Command]['syntax']);

			return;
		}

		/**
		 * Handle the command.
		 */
		switch($Message->Command) {

			case 'say':

				$Events->message($Message->Parts[1]);

				break;

			case 'info':

				$Events->message('I am developed with the Framework Noze. The developer is : Noze(1000069). The current version is : ' . NOZE_VERSION);

				break;
		}
	}

} 