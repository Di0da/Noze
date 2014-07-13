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
 * @filename Library.Packet.Handle.php
 */

namespace Noze\Packet;
use Noze\Network\Network;
use \Noze\Noze;
use \Noze\Utility\Debug;

class Handle {

	/**
	 * Class to which we can redirect events
	 *
	 * @var object
	 */
	private $Events = null;



	public function __construct()
	{
	}



	public function handlePacket($Type, $Packet, $Events) {

		$this->setEventHandler($Events);

		switch($Type) {

			case 'logout':

				/**
				 * Xat has closed the connection.
				 */
				$this->Events->onLogOut();

				break;

			case 'idle':

				/**
				 * Xat's telling us that we was not active for a while.
				 */
				if(isset($Packet['idle']['e'])) {

					$this->Events->onAway();
				}

				break;

			case 'gp':

				/**
				 * Xats sending us all groups powers assigned to the chat and the GBack if there is one.
				 * The packet look like this :
				 *      <gp p="0|0|1024|1024|4194308|0|0|0|0|0|0|0|0|" g106="#clear" />
				 *
				 * /!\ All this values are sending in bitwise : http://en.wikipedia.org/wiki/Bitwise_operation
				 */

				break;

			case 'q':

				/**
				 * XAT notice to change Ip and/or Port.
				 */
				$this->Events->onXatNotice($Packet['q']['d'], $Packet['q']['p']);

				break;

			case 'o':

				/*$Packet['o']['u'] = $this->parseUserId(@$Packet['u']['u']);
				Bot::$users[$Packet['o']['u']]['name'] = @$Packet['o']['n'];
				Bot::$users[$Packet['o']['u']]['registeredName'] = ((isset($Packet['o']['N']))?$Packet['o']['N']:'');
				Bot::$users[$Packet['o']['u']]['avatar'] = @$Packet['o']['a'];
				Bot::$users[$Packet['o']['u']]['homepage'] = @$Packet['o']['h'];
				Bot::$users[$Packet['o']['u']]['rank'] = $this->f2rank(@$Packet['o']['f']);*/

				break;

			case 'u':

				/**
				 * An user joined the chat. We put it in the global $Users variable.
				 */
				$Packet['u']['u'] = $this->parseUserId($Packet['u']['u']);

				Noze::$Users[$Packet['u']['u']]['id']               = $Packet['u']['u'];
				Noze::$Users[$Packet['u']['u']]['name']             = $Packet['u']['n'];
				Noze::$Users[$Packet['u']['u']]['registeredName']   = ((isset($Packet['u']['N']))?$Packet['u']['N']:'');
				Noze::$Users[$Packet['u']['u']]['avatar']           = $Packet['u']['a'];
				Noze::$Users[$Packet['u']['u']]['homepage']         = $Packet['u']['h'];
				Noze::$Users[$Packet['u']['u']]['rank']             = ((isset($Packet['u']['f']))?$this->f2rank($Packet['u']['f']):'guest');

				$data['UserId'] = $Packet['u']['u'];
				$data['old'] = ($Type=='o'||(isset($Packet['u']['s']))?true:false);

				$Message = new Message($data);
				$this->Events->onChannelJoin($this->Events, $Message);

				break;

			case 'l':

				/**
				 * An User left the chat or was kicked or was banned.
				 * We remove him from the global $Users variable.
				 */
				if(Noze::$Users[$Packet['l']['u']]) {

					unset(Noze::$Users[$Packet['l']['u']]);
				}
				break;

			case 'p':

				/**
				 * Private Message or Private Chat received
				 */
				$data['UserId'] =  $this->parseUserId($Packet['p']['u']);
				$data['Message'] = $Packet['p']['t'];

				if(isset($Packet['p']['d'])) {

					/**
					 * Private Chat
					 */
					$Message = new Message($data);
					$this->Events->onPrivateChat($this->Events, $Message);

				} else {

					/**
					 * Private Message
					 */
					$Message = new Message($data);
					$this->Events->onPrivateMessage($this->Events, $Message);
				}



				break;

			case 'm':

				/**
				 * A message has been posted in main chat.
				 */
				$data['Message'] = $Packet['m']['t'];

				/**
				 * Ignore all commands message starting with / (Like deleting a message, Typing etc)
				 */
				if(!isset($data['Message']) || substr($data['Message'], 0, 1) == '/') {

					break;
				}


				$data['old'] = ((isset($Packet['m']['s']))?true:false);

				/**
				 * Xat send sometimes the old messages, we ignore it so.
				 */
				if($data['old']) {
					break;
				}

				/**
				 * Get the Id of the user who has sent the message
				 */
				$data['UserId'] = ((isset($Packet['m']['u']))?$Packet['m']['u']:false);

				if($data['UserId']) {

					$data['UserId'] = $this->parseUserId($Packet['m']['u']);
				}

				$Message = new Message($data);
				$this->Events->onChannelMessage($this->Events, $Message);

				break;

			case 'z':

				/**
				 * The bot has been tickled by someone.
				 */
				$data['id'] = $this->parseUserId($Packet['z']['u']);
				$this->Events->answerTickle($data['id']);

				break;
		}
	}


	/**
	 * Sets the handler that is to be called when something happens
	 *
	 * @param object $Handler
	 *
	 * @return true
	 */
	public function setEventHandler($Handler) {

		$this->Events = $Handler;

		return true;
	}


	/**
	 * Parses the u value from a packet to get just the id
	 *
	 * @param int $UserId
	 *
	 * @return int
	 */
	public static function parseUserId($UserId) {

		if(substr_count($UserId,'_')>=1) {

			$UserId = substr($UserId,0,strpos($UserId,'_'));
		}

		return $UserId;
	}


	/**
	 * Converts an f value to a string containing the corresponding rank
	 * @param int $rank
	 *
	 * @return string
	 */
	private function f2rank($rank) {
		$rank = $this->parseUserId($rank);

		if($rank==-1)                               return 'guest';
		if((16 & $rank))                            return 'banned';
		if((1 & $rank)&&(2 & $rank))                return 'member';
		if((4 & $rank))                             return 'owner';
		if((32 & $rank)&&(1 & $rank)&&!(2 & $rank)) return 'main';
		if(!(1 & $rank)&&!(2 & $rank))              return 'guest';
		if((16 & $rank))                            return 'banned';
		if((2 & $rank)&&!(1 & $rank))               return 'mod';

		return 'guest';
	}
} 