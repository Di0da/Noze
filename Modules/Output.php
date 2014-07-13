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
 * @filename Modules.Output.php
 */

namespace Noze\Modules;
use \Noze;
use \Noze\Utility\Debug;
use \Noze\Utility\Request;
use \Noze\Configuration;

class Output implements \Noze\Module\ModuleInterface {



	/**
	 * Called when an user joined the chat.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param object    $Message
	 *
	 * @return bool
	 */
	public function onChannelJoin(\Noze\Noze $Noze, $Events, $Message) {

		/**
		 * Get the information about this User.
		 */
		$User = Request::getUser($Message->UserId);

		/**
		 * Here is a little function like an "auto-member", to make all Guest -> Member.
		 */
		if($User['rank'] == 'guest') {

			$Events->changeRank($Message->UserId, 'member');
		}

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
	public function onChannelMessage(\Noze\Noze $Noze, $Events,$Message) {

		if($Message->CommandCode != Configuration::getConfig('commands')['prefix']) {

			if(strpos(strtolower($Message->Raw), 'noze')) {

				$Events->message('You said Noze bro? He\'s my boss and coded me. (cool)');
			}
		}

	}


	/**
	 * Called when Xat send us the packet "idle", that mean we was in chat too long without talking.
	 *
	 * @param object    Noze\Noze $Noze
	 */
	public function onAway(\Noze\Noze $Noze){

		$Noze->Server->reConnect();
		$Noze->Network->join();
	}


	/**
	 * Called when Xat send us the packet "logout" (Generally, after the packet "idle"),
	 * that mean Xat has closed the Socket connection.
	 *
	 * @param object    Noze\Noze $Noze
	 */
	public function onLogOut(\Noze\Noze $Noze) {

		$Noze->Server->reConnect();
		$Noze->Network->join();
	}


	/**
	 * Called when Xat notify us to change Ip and/or Port.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param string    $Ip
	 * @param string    $Port
	 */
	public function onXatNotice(\Noze\Noze $Noze, $Ip, $Port) {

		$Noze->Server->reConnect($Ip, $Port);
		$Noze->Network->join();
	}


	/**
	 * Called when someone send a Private Chat to the bot.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param object    $Message
	 */
	public function onPrivateChat(\Noze\Noze $Noze, $Events, $Message) {

		$Events->privateChat($Message->UserId, 'Hey, you pced me ! (eek)');
	}


	/**
	 * Called when someone send a Private Message to the bot.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param object    $Events
	 * @param object    $Message
	 */
	public function onPrivateMessage(\Noze\Noze $Noze, $Events, $Message) {

		$Events->privateMessage($Message->UserId, 'Hey, you pmed me ! (eek)');
	}

} 