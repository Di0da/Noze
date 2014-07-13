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
 * @filename Modules.Response.php
 */

namespace Noze\Modules;
use \Noze;
use \Noze\Utility\Debug;

class Response implements \Noze\Module\ModuleInterface {


	/**
	 * Send a normal message.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param string    $Message
	 *
	 * @return bool
	 */
	public function message(Noze\Noze $Noze, $Message=null) {

		if(is_null($Message)) {

			return false;
		}

		$Noze->Server->send($Noze->Server->buildPacket('m', array('t'=>$Message, 'u'=>$Noze->Network->LoginInfos['i'])));

		return true;
	}


	/**
	 * Send a private message.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param int       $UserId
	 * @param string    $Message
	 *
	 * @return bool
	 */
	public function privateMessage(Noze\Noze $Noze, $UserId=null, $Message=null) {

		/**
		 * Verify the required data.
		 */
		if(is_null($Message) || !is_numeric($UserId)) {

			return false;
		}

		$Noze->Server->send($Noze->Server->buildPacket('p', array('u'=>$UserId, 't'=>$Message, 'd'=>$UserId)));

		return true;
	}


	/**
	 * Send a private message.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param int       $UserId
	 * @param string    $Message
	 *
	 * @return bool
	 */
	public function privateChat(Noze\Noze $Noze, $UserId=null, $Message=null) {

		/**
		 * Verify the required data.
		 */
		if(is_null($Message) || !is_numeric($UserId)) {

			return false;
		}

		$Noze->Server->send($Noze->Server->buildPacket('p', array('u'=>$UserId, 't'=>$Message, 's'=>2, 'd'=>$Noze->Network->LoginInfos['i'])));

		return true;
	}


	/**
	 * Change the rank of an user. /!\(The bot must have the required rank to do that)
	 *
	 * @param object    Noze\Noze $Noze
	 * @param int       $UserId
	 * @param string    $rank
	 *
	 * @return bool
	 */
	public function changeRank(Noze\Noze $Noze, $UserId=null, $rank=null) {

		$rankCmd = array(
			'owner'     => '/M',
			'moderator' => '/m',
			'member'    => '/e',
			'guest'     => '/r'
		);

		/**
		 * Check if the value is in the array and verify the required data.
		 */
		if(!isset($rankCmd[$rank]) || !is_numeric($UserId)) {

			return false;
		}

		$this->privateChat($Noze, $UserId, $rankCmd[$rank]);

		return true;
	}


	/**
	 * Tickle an user.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param int       $UserId
	 *
	 * @return bool
	 */
	public function tickle(Noze\Noze $Noze, $UserId=null) {

		/**
		 * Verify the required data.
		 */
		if(!is_numeric($UserId)) {

			return false;
		}

		$Noze->Server->send($Noze->Server->buildPacket('z', array('d'=>$UserId, 'u'=>$Noze->Network->LoginInfos['i'].'_0', 't'=>'/l')));

		return true;
	}

	/**
	 * When an user tickle the bot, you must send back this packet to display the bot's powers and others information about the bot.
	 *
	 * @param object    Noze\Noze $Noze
	 * @param int       $UserId
	 *
	 * @return mixed
	 */
	public function answerTickle(Noze\Noze $Noze, $UserId=null) {

		/**
		 * Verify the required data.
		 */
		if(!is_numeric($UserId)) {

			return false;
		}

		$Noze->Server->send($Noze->Server->buildPacket('z', array('d'=>$UserId, 'u'=>$Noze->Network->LoginInfos['i'].'_0', 't'=>'/a_NF')));

		return true;
	}
} 