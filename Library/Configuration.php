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
 * @filename Library.Configuration.php
 */

namespace Noze;
use \Noze\Utility\Debug;

class Configuration {

	/**
	 * Configuration variable
	 *
	 * @var array
	 */
	private static $Configuration = array ();


	/**
	 * We need it.
	 *
	 */
	private function __construct () {}


	/**
	 * Register variables with this configuration
	 *
	 * @param array $Config
	 *
	 * @return true
	 */
	public static function init (array $Config) {

		/**
		 * Merge existing variables with new ones
		 */
		self::$Configuration = array_merge (self::$Configuration, $Config);
		return true;
	}


	/**
	 * Returns one specific configuration variable
	 *
	 * @param mixed $Offset
	 *
	 * @return mixed
	 */
	public static function getConfig ($Offset) {

		if (!isset(self::$Configuration[$Offset])) {

			Debug::debug('Configuration setting "' . $Offset . '" is not set.');
		}

		return self::$Configuration[$Offset];
	}


	/**
	 * Checks if one or more variables are set
	 *
	 * @param string|array $Offset
	 *
	 * @return bool
	 */
	public static function hasConfig ($Offset) {

		if (is_array($Offset)) {
			foreach ($Offset as $Key)
			{
				if (!self::hasConfig ($Key))
				{
					return false;
				}
			}

			return true;
		} else {
			return isset(self::$Configuration[$Offset]);
		}
	}


	/**
	 * Sets the offset to a new variable
	 *
	 * @param mixed $Offset
	 * @param mixed $Value
	 *
	 * @return true
	 */
	public static function setConfig($Offset, $Value)
	{
		self::$Configuration[$Offset] = $Value;
		return true;
	}
} 