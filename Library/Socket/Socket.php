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
 * @filename Library.Socket.Socket.php
 */

namespace Noze\Socket;
use \Noze\Configuration;
use \Noze\Noze;
use \Noze\Packet\Handle;
use \Noze\Utility\Debug;

class Socket {

	/**
	 * Amount of time a connection attempt may take before timing out
	 *
	 * @const int
	 */
	const CONN_TIMEOUT = 10;

	/**
	 * The last packet received
	 *
	 * @var null
	 */
	public $HandlePacket = null;

	/**
	 * Number of attempt to trying to reconnect
	 *
	 * @var object
	 */
	private $ReconnectTimes = 0;

	/**
	 * The time in UNIX timestamp when the connection was established
	 *
	 * @var int
	 */
	protected $ConnectTime = 0;

	/**
	 * Resource for the socket
	 *
	 * @var resource
	 */
	protected $Socket = null;

	/**
	 * Connection status
	 *
	 * @var int
	 */
	public static $Status = Status::NOT_CONNECTED;

	/**
	 * Remote ip we want to connect to
	 *
	 * @var string
	 */
	protected $Ip = null;

	/**
	 * Remote port for the address we want to connect to
	 *
	 * @var int
	 */
	protected $Port = null;

	/**
	 * Remote login Ip
	 *
	 * @var int
	 */
	public static $LoginIp = null;

	/**
	 * Name of the Room
	 *
	 * @var string
	 */
	protected $RoomName = null;

	/**
	 * Id of the Room
	 *
	 * @var string
	 */
	protected $RoomId = null;

	/**
	 * Class to which we can redirect events
	 *
	 * @var object
	 */
	private $Events = null;


	/**
	 * Initiates new Socket object
	 *
	 * @param null $RoomName
	 */
	public function __construct($RoomName = NULL) {

		if(is_null($RoomName)) {

			Debug::debug('The RoomName is not defined.');
		}

		/*
		 * Get info about the room
		 */
		$RoomInfo = $this->getRoomInfo($RoomName);

		foreach($RoomInfo as $key => $value) {
			//Stock in variables
			$this->$key = $value;
		}

		/*
		 * Get the Ip, Port and LoginIp from Xat
		 */
		$this->getHost();

	}


	/**
	 * Close the current socket
	 */
	public function __destruct() {

		/**
		 * The socket is still CONNECTED, close it
		 */
		if ($this->isConnected()) {

			$this->disconnect();
		}
	}


	/**
	 * Sets the handler that is to be called when something happens
	 *
	 * @param object $Handler
	 * @return true
	 */
	public function setEventHandler($Handler) {

		$this->Events = $Handler;

		return true;
	}


	/**
	 * New socket connection
	 */
	public function connect($Ip=null, $Port=null) {

		/**
		 * We are already connected
		 */
		if($this->isConnected()) {

			return true;
		} elseif (self::$Status == Status::CONNECTING && Noze::$Socket != NULL) {

			self::$Status = Status::CONNECTED;
			return true;
		}

		/**
		 * Check if we must use a special Ip and Port
		 */
		if(!is_null($Ip) && !is_null($Port)) {

			$this->Ip   = $Ip;
			$this->Port = $Port;
		}

		/**
		 * The Port or the Ip is missing
		 */
		if(!$this->Port || !$this->Ip) {

			Debug::debug("IP and/or Port is not defined, re-trying.");

			$this->ReconnectTimes++;

			/**
			 * Check the TimeOut
			 */
			if($this->ReconnectTimes < self::CONN_TIMEOUT) {

				$this->getHost();
				$this->connect();
			} else {

				return false;
			}

		}

		/**
		 * Connecting...
		 */
		self::$Status = Status::CONNECTING;
		Debug::debug("Connecting to " . $this->Ip . ":" . $this->Port);

		Noze::$Socket = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP);

		/**
		 * Failed to create the socket.
		 */
		if(!Noze::$Socket) {

			self::$Status = Status::DISCONNECTED;
			Debug::debug('yolo1');
			Debug::debug(socket_strerror(socket_last_error(Noze::$Socket)));

			return false;
		}

		/**
		 * Trying to connect to the socket.
		 */
		if(!socket_connect(Noze::$Socket, $this->Ip, $this->Port)) {

			/**
			 * Increment the variable.
			 */
			$this->ReconnectTimes++;

			/**
			 * Check the TimeOut.
			 */
			if($this->ReconnectTimes < self::CONN_TIMEOUT) {

				Debug::debug("Failed to connect, reconnecting.");

				$this->getHost();
				$this->connect();
			} else {

				self::$Status = Status::DISCONNECTED;

				Debug::debug("Failed to connect and the max attempt to trying to connect has been reached, the program has quit.");

				return false;
			}
		} else {

			/**
			 * Connected.
			 */
			self::$Status = Status::CONNECTED;
			$this->ConnectTime = time();

			Debug::debug("Connected.");

			return true;
		}

		return false;
	}


	/**
	 * Disconnect the socket connection.
	 */
	private function disconnect() {

		if (self::$Status == Status::DISCONNECTED) {

			return true;
		}

		/**
		 * Terminate connection.
		 */
		@socket_close(Noze::$Socket);
		self::$Status = Status::DISCONNECTED;
		Noze::$Socket = NULL;

		Debug::debug("Disconnect.");

		return true;
	}


	/**
	 * Send the packet to the socket.
	 *
	 * @param string $data
	 */
	public function send($data) {

		/**
		 * We are not connected.
		 */
		if (!$this->isConnected()) {

			Debug::debug("Cannot send() on socket that is not connected.");
			$this->disconnect();

			return false;
		}

		if(!socket_write(Noze::$Socket, $data.chr(0), strlen($data)+1)) {

			Debug::debug("Cannot send() on socket, trying to reConnect().");
			$this->reConnect();

			return false;
		}

		Debug::debug("[Sending] " . $data);

		return true;
	}


	/**
	 * Read all packets from xat.
	 *
	 * @param bool $Parse
	 * @param bool $Handle
	 *
	 * @return string
	 */
	public function read($Parse=true, $Handle=true) {

		/**
		 * We are not connected.
		 */
		if (!$this->isConnected()) {

			Debug::debug("Cannot read() on socket that is not connected.");
			$this->disconnect();

			return false;
		}

		/**
		 * Get the status of the socket.
		 */
		$status = @socket_get_status(Noze::$Socket);


		/**
		 * Check the timeout.
		 */
		if(isset($status['timed_out'])) {

			$this->disconnect();

			return false;
		}

		/**
		 * Try to read the socket.
		 */
		$SocketResponse = @socket_read(Noze::$Socket, 1460);
		if(!$SocketResponse) {

			Debug::debug("Cannot read() on socket that is not connected.");
			$this->disconnect();

			return false;
		}

		/**
		 * Check if the packet is closed by the sign ">".
		 */
		if($SocketResponse{(strlen($SocketResponse)-2)} != '>') {

			/**
			 * Try to read again.
			 */
			$SocketResponse .= $this->read(false, false);

			Debug::debug("Cannot read() the end of the packet, trying to read() again.");

		}

		/**
		 * Parse the packet.
		 */
		if($Parse) {

			return $this->parsePacket($SocketResponse, $Handle);
		} else {

			return $SocketResponse;
		}



	}


	/**
	 * Returns whether the socket is connected or not.
	 *
	 * @return bool
	 */
	public function isConnected() {

		return (self::$Status == Status::CONNECTED);
	}


	/**
	 * Reconnect to the socket.
	 *
	 * @param string $Ip
	 * @param string $Port
	 */
	public function reConnect($Ip=null, $Port=null) {

		$this->disconnect();
		$this->connect($Ip, $Port);
	}


	/**
	 * Parse a packet received from Xat.
	 *
	 * @param string    $packets
	 * @param bool      $handle
	 *
	 * @return mixed
	 */
	public function parsePacket($packets, $handle=true) {

		$packets = explode('/>',$packets);
		$packers = array();

		foreach((Array)$packets as $packet) {

			$packet = trim($packet);
			$packet = str_replace('', '', $packet);

			if(!empty($packet)) {

				$last3 = substr( str_replace(chr(0), '', $packet), (strlen($packet)-3) );

				if($last3 != ' />') {

					$packet .= ' />';
				}

				$data = html_entity_decode($packet);

				Debug::debug("[Receiving] " . $data);

				$packers[] = $packet;
			}
			if(strlen($packet)<5) {

				return false;
			}

			if($packet{0} != '<') {

				$packers[count($packers) - 2] = $packers[count($packers) - 2] . " ";
				$x = strrev($packers[count($packers) - 2]);

				if($x{0} == '"'){

					$packet = trim($packers[count($packers) - 2]) . '' .$packet . " ";
				} else {

					$packet = trim($packers[count($packers) - 2]) . '' . $packet;
				}
			}

			$packet = str_replace('/>','',$packet);
			$packet = $packet . '/>';
			$pack = $this->getValuePacket($packet);
			$type = $pack['type'];

			foreach($pack as $k=>$v) {

				$pack[$k] = $v;
			}

			$this->HandlePacket[$type] = $pack;

			if($handle) {

				$Handle = new Handle();
				$Handle->handlePacket($type, $this->HandlePacket, $this->Events);

			} else {

				return $pack;
			}
		}

		return $pack;
	}


	/**
	 * Get all value in the packet.
	 *
	 * @param string $Packet
	 *
	 * @return array
	 */
	private function getValuePacket($Packet) {

		$p = trim($Packet,'<');
		$p1 = trim($Packet,'<');

		$p1 = '<' . $p1;

		$p = explode('/>',$p);
		$p = $p[0];
		$x = explode(' ',$p);
		$type = $x[0];

		$packet['type'] = $type;

		$xml = simplexml_load_string($p1);
		$x = $this->parseXml($xml);
		$attributes = @$x['@attributes'];

		foreach((Array)$attributes as $type=>$var) {

			$packet[$type] = $var;
		}

		return $packet;
	}


	/**
	 * Build a packet for sending.
	 *
	 * @param string $type
	 * @param array  $details
	 *
	 * @return string
	 */
	public static function buildPacket($type=null, $details=array()) {

		if(is_null($type)) {
			return '';
		}

		$packet = '<'.$type.' ';

		foreach($details as $Key=>$Value) {

			//if((array)@$Value != '' || ($type=='j2' && $Key=='h') || !empty($Value)) {

				$packet .= $Key.'="'.$Value.'" ';
			//}
		}

		$packet .= '/>';

		return $packet;
	}


	/**
	 * Parse a xml string.
	 *
	 * @param string $xml
	 *
	 * @return array
	 */
	private function parseXml($xml) {

		$array = json_decode(json_encode($xml), TRUE);

		foreach ((Array) array_slice($array, 0) as $key => $value) {

			if (is_array(@$value)) $array[$key] = $this->parseXml($value);
		}
		return $array;
	}


	/**
	 * Get the Ip, Port and the LoginIp from Xat.
	 */
	private function getHost() {

		$file = simplexml_load_file('http://xat.com/web_gear/chat/ip.htm?init='.(time()/1000));

		$sock = array("173.255.132.116","173.255.132.117","173.255.132.118","173.255.132.119");


		$this->Ip       = $sock[$this->getDom($this->RoomId)];
		$this->Port     = 10000 + floor(rand(0,38));
		self::$LoginIp  = $file->Attributes()->fwd;
	}


	/**
	 * Get the position in the Dom on the XML file.
	 *
	 * @param int $chat
	 *
	 * @return int
	 */
	private function getDom($chat) {

		if($chat == 8)
			return 0;
		else if($chat < 8)
			return 3;
		else
			return ($chat & 96) >> 5;
	}


	/**
	 * Get the ID by the room name or the name by the ID.
	 *
	 * @param string|int $Name
	 *
	 * @return array|bool
	 */
	private function getRoomInfo($Name) {

		if(is_numeric($Name)) {
			$url = 'http://xat.com/xat'.$Name;
		} else {
			$url = 'http://xat.com/'.$Name;
		}

		$cont = str_replace(array("\n","\r","\t"), '', @file_get_contents($url));
		$RoomId = $this->subStr($cont, '<a href="http://xat.com/web_gear/chat/embed.php?id=', '&GroupName=');
		$RoomName = $this->subStr($cont, '<meta property="og:url" content="http://xat.com/', '"');
		$title = $this->subStr($cont, '<title>', '</title>');
		$title = explode(' ', $title);

		if(!is_numeric($RoomId) || @$title[0] == 'xat') {

			return false;
		} else {

			$config = array(
				'RoomId'	=>	$RoomId,
				'RoomName'	=>	$RoomName
			);

			/**
			 * Set the new configuration.
			 */
			Configuration::setConfig('room',array(
					'name'      => $RoomName,
					'id'        => $RoomId,
					'password'  => Configuration::getConfig('room')['password']
				)
			);

			return $config;
		}
	}


	/**
	 * Parse a string with subStr.
	 *
	 * @param string $inputStr
	 * @param string $deliLeft
	 * @param string $deliRight
	 *
	 * @return string
	 */
	private function subStr($inputStr, $deliLeft, $deliRight) {

		$posLeft = stripos($inputStr, $deliLeft) + strlen($deliLeft);
		$posRight = stripos($inputStr, $deliRight, $posLeft);

		return substr($inputStr, $posLeft, $posRight - $posLeft);
	}
} 