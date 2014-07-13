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
 * @filename Library.Packet.Message.php
 */

namespace Noze\Packet;

class Message {

	/**
	 * Raw line we have parsed
	 *
	 * @var string
	 */
	public $Raw = null;

	/**
	 * Raw message in parts
	 *
	 * @var array
	 */
	public $Parts = array ();

	/**
	 * Command
	 *
	 * @var string
	 */
	public $Command = null;

	/**
	 * Message
	 *
	 * @var string
	 */
	public $Message = null;

	/**
	 * Code of the command
	 *
	 * @var array
	 */
	public $CommandCode = null;

	/**
	 * Message in parts
	 *
	 * @var array
	 */
	public $Arguments = array ();

	/**
	 * Id of the user who has sent the message
	 *
	 * @var array
	 */
	public $UserId = null;


	/**
	 * Initiate new parser class
	 *
	 * @param array $Data
	 */
	public function __construct($Data) {

		/**
		 * Check if we have a Message
		 */
		if(isset($Data['Message'])) {

			$this->Raw          = $Data['Message'];
			$this->Parts        = explode(chr(32),trim($Data['Message']),2);
			$this->CommandCode  = substr($this->Parts[0],0,1);
			$this->Command      = substr($this->Parts[0],1);

			/**
			 * There are more than one word in the message
			 */
			if(count($this->Parts) > 1) {

				$this->Message      = $this->Parts[1];
				$this->Arguments    = explode(chr(32), $this->Parts[1]);
			}
		}

		/**
		 * Check if we have an UserId
		 */
		if(isset($Data['UserId'])) {

			$this->UserId   = $Data['UserId'];
		}
	}
} 