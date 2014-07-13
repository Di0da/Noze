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
 * @filename Library.Network.Network.php
 */

namespace Noze\Network;
use \Noze\Configuration;
use \Noze\Socket\Socket;
use \Noze\Utility\Debug;
use \Noze\Utility\Request;


class Network extends Socket {

	/**
	 * Information about the last login on Xat.
	 *
	 * @var array
	 */
	public $LoginInfos = array();

	/**
	 * Information about the bot.
	 *
	 * @var array
	 */
	private $BotInfos = array();

	/**
	 * Information about the room.
	 *
	 * @var array
	 */
	private $RoomInfos = array();



	/**
	 * Constructor to get all the required information.
	 */
	public function __construct() {

		$this->getInformation();
	}


	/**
	 * Function to join a room.
	 */
	public function join() {

		$this->getInformation();

		$this->connectOnXat($this->BotInfos['username'], $this->BotInfos['password']);

		$this->send('<y r="'. $this->RoomInfos['id'] . '" m="1" v="0" u="' . $this->LoginInfos['i'] .'" />');
		$this->read(true, true);

		$packet = $this->HandlePacket['y'];
		$i = $this->LoginInfos;

		/**
		 * Build the j2 packet.
		 */
		$j2['cb'] 	= 	$packet['c'];

		if(isset($packet['au'])) {
			$j2['Y']    = 2;
		}

		$j2['l5'] 	= 	'';
		$j2['l4'] 	= 	rand(10, 500);
		$j2['l3'] 	= 	rand(10, 500);
		$j2['l2'] 	= 	'0';
		$j2['q'] 	= 	'1';
		$j2['y'] 	= 	$packet['i'];
		$j2['k'] 	= 	$i['k1'];
		$j2['k3'] 	= 	$i['k3'];
		if(isset($i['d1']))
			$j2['d1'] = $i['d1'];
		$j2['z'] 	= 	'12';
		$j2['p'] 	= 	'0';
		$j2['c'] 	= 	$this->RoomInfos['id'];
		$j2['r'] 	= 	(is_string($this->RoomInfos['password'])) ? $this->RoomInfos['password'] : '';
		$j2['f'] 	= 	(is_string($this->RoomInfos['password'])) ? '6' : '0';
		$j2['e'] 	= 	(is_string($this->RoomInfos['password'])) ? '1' : '';
		$j2['u'] 	= 	$i['i'];

		$j2['d0'] 	= 	(isset($i['d0'])) ? $i['d0'] : $i['d0'];

		for($x=2; $x<=15; $x++) {

			if(isset($i['d'.$x])) {

				$j2['d'.$x] = $i['d'.$x];
			}

		}

		if(isset($i['dO'])) $j2['dO'] = $i['dO'];
		if(isset($i['dx'])) $j2['dx'] = $i['dx'];
		if(isset($i['dt'])) $j2['dt'] = $i['dt'];
		$j2['N']	=	$i['n'];
		$j2['n']	=	$this->BotInfos['name'];
		$j2['a']	=	$this->BotInfos['avatar'];
		$j2['h']	=	$this->BotInfos['home'];
		$j2['v']	=	(isset($packet['v'])) ? $packet['v']:'0';

		$this->send(Socket::buildPacket('j2', $j2));
	}


	/**
	 * Function to login on xat with and username and a password.
	 *
	 * @param string $user
	 * @param string $pass
	 *
	 * @return bool|array
	 */
	private function connectOnXat($user, $pass) {

		$postData = "Locked=NC&Login=Login&NameEmail=$user&Pin=0&Protected=NC&UserId=0&cp=&k2=0&mode=0&password=$pass";

		$Response = Request::post('http://xat.com/web_gear/chat/register.php', $postData, 'http://xat.com/web_gear/chat/register.php');


		/**
		 * Check the status of the response.
		 */
		if(!$Response['status']) {


			return false;
		}

		/**
		 * Select the Password and the UserId from the Response.
		 */
		$Response   = $Response['content'];
		$Password   = $this->getBetween(strtolower($Response),strtolower($user)."&pw=",'"');
		$UserId     = $this->getBetween(strtolower($Response),'<input type="hidden" name="UserId" value="','"');

		/**
		 * Cannot get the Password.
		 */
		if($Password == false) {

			return false;
		}

		$p = fsockopen(self::$LoginIp, 10002,$e,$e,1);

		stream_set_timeout($p,2);

		fwrite($p, '<y r="8" v="0" u="'.$UserId.'" />'.chr(0));
		$x = trim(fread($p, 1024));

		fwrite($p, '<v p="'.$pass.'" n="'.$user.'" />'.chr(0));
		$x = trim(fread($p, 1024));


		$this->parsePacket($x);


		$this->LoginInfos = $this->HandlePacket['v'];


		if(count($this->LoginInfos) <= 4) {

			return false;
		}

		return $this->LoginInfos;
	}


	/**
	 * Function to get a text by explode.
	 *
	 * @param string    $content
	 * @param int       $start
	 * @param int       $end
	 *
	 * @return string|bool
	 */
	private function getBetween($content, $start, $end) {

		$r = explode($start, $content);

		if (isset($r[1])) {

			$r = explode($end, $r[1]);

			return $r[0];
		}

		return false;
	}


	/**
	 * Get information about the room and the bot.
	 */
	private function getInformation() {

		$this->BotInfos     = Configuration::getConfig('bot');
		$this->RoomInfos    = Configuration::getConfig('room');
	}
} 