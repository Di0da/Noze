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
 * Thanks to Jedi http://xat.me/Jedi for his help about packets
 * Also thanks to Remco Pander for his Manager of Modules
 *
 * @package Noze
 * @author Emeric Fèvre https://twitter.com/NozeAres <zoro.fmt@gmail.com>
 * @version 0.1
 * @copyright Copyright © 2014, Emeric Fèvre
 *
 * @filename Noze.php
 */

namespace Noze;

/**
 * Version
 */
define ('NOZE_VERSION', '0.1');

/**
 * Set  no time limit
 */
error_reporting (E_ALL);
set_time_limit (0);
ignore_user_abort (true);
chdir (__DIR__);
date_default_timezone_set ('Europe/Berlin');

/**
 * Load required file
 */
require_once './Config/Config.php';
require_once './Library/AutoLoader.php';

/**
 * Initialise configuration
 */
Configuration::init($configuration);

class Noze {

	/**
	 * Instance of the Modules manager
	 *
	 * @var object
	 */
	public $Modules = null;

	/**
	 * Instance of the Server
	 *
	 * @var object
	 */
	public $Server = null;

	/**
	 * Instance of the Network
	 *
	 * @var object
	 */
	public $Network = null;

	/**
	 * Socket resource
	 *
	 * @var object
	 */
	public static $Socket = null;

	/**
	 * Users connected
	 *
	 * @var object
	 */
	public static $Users = null;



	/**
	 * Constructor, initialize all instances.
	 */
	public function __construct () {

		/**
		 * Find out if our configuration contains any list of priorities.
		 */
		if (Configuration::hasConfig('modules.priority')) {

			$Priorities = Configuration::getConfig('modules.priority');
		} else {

			$Priorities = array ();
		}

		/**
		 * Initialize Modules instance.
		 */
		$this->Modules = new Module\Manager($Priorities);
		$this->Modules->addPrefixArgument(array($this));

		/**
		 * Initialize Server instance, Socket resource.
		 */
		$Room = Configuration::getConfig('room');

		$this->Server = new Socket\Socket($Room['name']);
		$this->Server->setEventHandler($this->Modules);
		$this->Server->connect();

		/**
		 * Initialize Network instance.
		 */
		$this->Network = new Network\Network();
		$this->Network->join();

	}


	public function start() {

		if($this->Server->read() == false) {

			$this->Server->connect();
		}

		while(Noze::$Socket != null) {

			$this->Server->read();
		}
	}
}

/**
 * Initialize the Bot
 */
$Noze = new Noze();
$Noze->start();