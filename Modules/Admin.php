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
 * @filename Modules.Admin.php
 */

namespace Noze\Modules;
use \Noze;
use \Noze\Configuration;
use \Noze\Utility\Debug;
use \Noze\Utility\Request;

class Admin implements \Noze\Module\ModuleInterface {

	/**
	 * Commands configuration.
	 *
	 * @const array
	 */
	private $ConfigCommands = array();

	/**
	 * All administrators of the Bot.
	 *
	 * @var array
	 */
	private $BotAdmins = array();

	/**
	 * List of admin commands.
	 *
	 * @var array
	 */
	private $Commands = array();



	/**
	 * Constructor, set up the configuration.
	 */
	public function __construct() {

		$this->ConfigCommands   = Configuration::getConfig('commands');
		$this->BotAdmins        = Configuration::getConfig('bot')['admin'];

		/**
		 * List of commands and arguments needed. You must put the name of the command in lowercase.
		 */
		$Commands = array (
			'load'     => array (
				'params' => 1,
				'syntax' => $this->ConfigCommands['prefix'] . 'Load [Module]'
			),
			'unload'   => array (
				'params' => 1,
				'syntax' => $this->ConfigCommands['prefix'] . 'Unload [Module]'
			),
			'reload'   => array (
				'params' => 1,
				'syntax' => $this->ConfigCommands['prefix'] . 'Reload [Module]'
			),
			'loadedmodules'   => array (
				'params' => 0,
				'syntax' => $this->ConfigCommands['prefix'] . 'LoadedModules'
			),
			'timemodule'   => array (
				'params' => 1,
				'syntax' => $this->ConfigCommands['prefix'] . 'TimeModule [Module]'
			),
			'botedit'   => array (
				'params' => 2,
				'syntax' => $this->ConfigCommands['prefix'] . 'BotEdit [Name|Avatar|Home|Admin] [Text|Image|Link||Add|Remove|Show] [UserId]'
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
		$Message->Command   = strtolower($Message->Command);
		$Message->Arguments = array_map('strtolower', $Message->Arguments);

		/**
		 * We check if we have the commandCode, and if the command exist,
		 * else it's just a simple phrase or a normal command and we do nothing here. :)
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
	 * Checks if the given user has permission to perform an action.
	 *
	 * @param $UserId
	 *
	 * @return bool
	 */
	public function hasPermission($UserId) {

		return (in_array($UserId,$this->BotAdmins));
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
		 * Verify all required information.
		 */
		if(!$this->hasPermission($Message->UserId)) {
			//Check if the user has the required rank.
			$Events->message('You are not administrator of the bot.');

			return;
		} elseif(count($Message->Arguments) < $this->Commands[$Message->Command]['params']) {
			//Check if the user has given enough parameters.
			$Events->message('Not enough parameters given. Syntax: ' . $this->Commands[$Message->Command]['syntax']);

			return;
		}

		/**
		 * Handle the command.
		 */
		switch($Message->Command) {

			case 'timemodule':

				/**
				 * Get the UNIX time.
				 */
				$Time = $Noze->Modules->timeLoaded($Message->Arguments[0]);

				/**
				 * If $Time is false, that mean the Module is not loaded and/or doesn't exist.
				 */
				if(!$Time) {

					$Events->message('This Module is not loaded.');
					break;
				}

				$Events->message('The Module is loaded since : ' . date("H:i:s d/m/Y",$Time) . '.');

				break;

			case 'loadedmodules':

				/**
				 * Get the loaded Modules and implode the array as a string.
				 */
				$Modules = $Noze->Modules->getLoadedModules();
				$Modules = implode(", ", $Modules);

				$Events->message('Modules loaded : ' . $Modules . '.');

				break;

			case 'load':

				/**
				 * Load the Module.
				 */
				$Module = $Noze->Modules->load($Message->Arguments[0]);

				switch($Module) {
					//AlreadyLoaded
					case 'AL':
						$Events->message('The Module [' . $Message->Arguments[0] . '] is already loaded.');
						break;

					//Loaded
					case 'L':
						$Events->message('Module [' . $Message->Arguments[0] . '] loaded successfully.');
						break;

					//NotFound
					case 'NF':
						$Events->message('The Module [' . $Message->Arguments[0] . '] was not found.');
						break;
				}

				break;

			case 'unload':

				/**
				 * Unload the Module.
				 */
				$Module = $Noze->Modules->unload($Message->Arguments[0]);

				//AlreadyUnloaded
				if($Module == 'AU') {

					$Events->message('The Module [' . $Message->Arguments[0] . '] is already unloaded or doesn\'t exist.');
				} else {

					$Events->message('Module [' . $Message->Arguments[0] . '] unloaded successfully.');
				}

				break;

			case 'reload':

				/**
				 * Check if we must reload all Modules.
				 */
				if($Message->Arguments[0] == "all") {

					/**
					 * Get the list of the loaded Modules.
					 */
					$LoadedModules = $Noze->Modules->getLoadedModules();

					/**
					 * For each Modules, we reload it.
					 */
					foreach($LoadedModules as $Module) {

						$this->reloadModule($Noze, $Events, $Module);

						/**
						 * To avoid spam.
						 */
						usleep(500000);
					}

					break;
				}

				/**
				 * Else there is just one Module to reload.
				 */
				$this->reloadModule($Noze, $Events, $Message->Arguments[0]);

				break;

			case 'botedit':

				/**
				 * We call the function botEditHandle.
				 *
				 * I prefer doing a function when the code is too big,
				 * so we can keep our general Handle clean without a big code.
				 */
				$this->botEditHandle($Noze, $Events, $Message);

				break;

		}
	}


	/**
	 * Function to reload a Module and send the response.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param string    $Module
	 */
	private function reloadModule(Noze\Noze $Noze, $Events, $Module) {

		$ModuleStatus = $Noze->Modules->reload($Module);

		switch($ModuleStatus) {
			//AlreadyUnloaded
			case 'AU':
				$Events->message('The Module [' . $Module . '] doesn\'t exist and cannot be reloaded.');
				break;

			//AlreadyLoaded
			case 'AL':
				$Events->message('The Module [' . $Module . '] is already loaded.');
				break;

			//Loaded
			case 'L':
				$Events->message('Module [' .  $Module . '] reloaded successfully.');
				break;

			//NotFound
			case 'NF':
				$Events->message('Failed to reload the Module [' .  $Module . '].');
				break;
		}
	}


	/**
	 * Handle the botEdit command.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param object    $Message
	 */
	private function botEditHandle(Noze\Noze $Noze, $Events, $Message) {

		switch($Message->Arguments[0]) {

			case 'name':

				/**
				 * Explode the Message in 2 part, and set the configuration with the second part.
				 */
				$Name = explode(chr(32),trim($Message->Message),2);

				$configuration = Configuration::getConfig('bot');
				$configuration[$Message->Arguments[0]] = $Name[1];

				Configuration::setConfig('bot',$configuration);

				/**
				 * We must reconnect and join again the room to display our new beautiful name.
				 */
				$Noze->Server->reConnect();
				$Noze->Network->join();

				break;

			case 'avatar':

				/**
				 * Explode the Message in 2 part, and set the configuration with the second part.
				 */
				$Avatar = explode(chr(32),trim($Message->Message),2);

				$configuration = Configuration::getConfig('bot');
				$configuration[$Message->Arguments[0]] = $Avatar[1];

				Configuration::setConfig('bot',$configuration);

				/**
				 * We must reconnect and join again the room to display our new beautiful avatar.
				 */
				$Noze->Server->reConnect();
				$Noze->Network->join();
				break;

			case 'home':

				/**
				 * Explode the Message in 2 part, and set the configuration with the second part.
				 */
				$Home = explode(chr(32),trim($Message->Message),2);

				$configuration = Configuration::getConfig('bot');
				$configuration[$Message->Arguments[0]] = $Home[1];

				Configuration::setConfig('bot',$configuration);

				/**
				 * We must reconnect and join again the room to display our new beautiful homepage.
				 */
				$Noze->Server->reConnect();
				$Noze->Network->join();
				break;

			case 'admin':

				switch($Message->Arguments[1]) {

					case 'add':

						/**
						 * Check if the user has given enough parameters.
						 */
						if(count($Message->Arguments) < 3) {

							$Events->message('Not enough parameters given. Syntax: ' . $this->Commands[$Message->Command]['syntax']);

							break;
						}

						$UserId = $Message->Arguments[2];

						/**
						 * Check if it's a numeric value.
						 */
						if(!is_numeric($UserId)) {

							/**
							 * Try to get the Id by his username.
							 */
							$UserId = Request::getIdName($UserId);
							$Name = '';
						} else {

							/**
							 * Try to get his username by his Id.
							 */
							$Name = Request::getIdName($UserId);
						}

						/**
						 * Verify if the user exist.
						 */
						if($Name == "#" || $UserId == 0) {

							$Events->message('This user doesn\'t exist.');
							break;
						}


						/**
						 * Check if the user is not already added.
						 */
						if(in_array($UserId, $this->BotAdmins)) {

							$Events->message('This user is already added.');
							break;
						}

						/**
						 * Get the old value and change the value in the configuration.
						 */
						$configuration = Configuration::getConfig('bot');
						array_push($configuration[$Message->Arguments[0]],$UserId);

						Configuration::setConfig('bot',$configuration);

						$this->BotAdmins = $configuration['admin'];

						$Events->message('The UserId [' . $UserId  . '] has been added successfully.');
						break;

					case 'remove':

						/**
						 * Check if the user has given enough parameters.
						 */
						if(count($Message->Arguments) < 3) {

							$Events->message('Not enough parameters given. Syntax: ' . $this->Commands[$Message->Command]['syntax']);

							break;
						}

						$UserId = $Message->Arguments[2];

						/**
						 * Check if it's a numeric value.
						 */
						if(!is_numeric($UserId)) {

							/**
							 * Try to get the Id by his username.
							 */
							$UserId = Request::getIdName($UserId);
						}

						/**
						 * Verify if the user exist.
						 */
						if($UserId == 0) {

							$Events->message('This user doesn\'t exist.');
							break;
						}

						/**
						 * Check if the user is admin.
						 */
						if(!in_array($UserId, $this->BotAdmins)) {

							$Events->message('This user is not admin.');
							break;
						}

						/**
						 * Get the old value and remove the value in the configuration then, save the new configuration.
						 */
						$configuration = Configuration::getConfig('bot');

						if(($Key = array_search($UserId, $configuration[$Message->Arguments[0]])) !== false) {

							unset($configuration[$Message->Arguments[0]][$Key]);
						}

						Configuration::setConfig('bot',$configuration);

						$this->BotAdmins = $configuration['admin'];

						$Events->message('The UserId [' . $UserId  . '] has been removed successfully.');

						break;

					case 'show':

						/**
						 * Get the admins of the bot and implode the array as a string.
						 */
						$Admins = implode(", ", $this->BotAdmins);

						$Events->message('Admins of the Bot : ' . $Admins . '.');

						break;

					default:

						/**
						 * This argument doesn't exist.
						 */
						$Events->message('This argument doesn\'t exist. Syntax: ' . $this->Commands[$Message->Command]['syntax']);
				}
				break;

			default:

				/**
				 * This argument doesn't exist.
				 */
				$Events->message('This argument doesn\'t exist. Syntax: ' . $this->Commands[$Message->Command]['syntax']);

		}
	}
} 