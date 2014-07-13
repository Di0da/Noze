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
 * @filename Library.Utility.Debug.php
 */

namespace Noze\Utility;
use \Noze\Configuration;

abstract class Debug {

	/**
	 * Whether we are using the debug mode
	 * @var bool
	 */
	private static $Activate = false;

	/**
	 * Whether we are using the debug mode
	 * @var bool
	 */
	private static $Intro = '[DEBUG]';

	/**
	 * File name of the backtrace
	 * @var string
	 */
	private static $File = '';

	/**
	 * Line number of the backtrace
	 * @var int
	 */
	private static $Line = 0;

	/**
	 * Name of the function of the backtrace
	 * @var string
	 */
	private static $Function = '';


	/**
	 * Display the debug message in the console
	 *
	 * @param string    $data
	 */
	public static function debug($data) {

		/**
		 * Get the backtrace
		 */
		$backtrace = debug_backtrace();

		if(isset($backtrace[0]['file'])) {

			self::$File = $backtrace[0]['file'];
		}

		if(isset($backtrace[0]['line'])) {

			self::$Line = $backtrace[0]['line'];
		}

		if(isset($backtrace[1]['function'])) {

			self::$Function = $backtrace[1]['function'];
		}

		/**
		 * Set up the array to display in the console
		 */
		if(strlen('Line') > strlen(self::$Line)) {
			$CountLine = strlen('Line')+2;
		} else {
			$CountLine = strlen(self::$Line)+2;
		}

		if(strlen('Function') > strlen(self::$Function)) {
			$CountFunction = strlen('Function')+2;
		} else {
			$CountFunction = strlen(self::$Function)+2;
		}

		/**
		 * Load configuration
		 */
		$Configuration = Configuration::getConfig('debug');
		self::$Activate = $Configuration['activate'];
		self::$Intro = $Configuration['intro'];

		/**
		 * The debug mode is activate
		 */
		if(self::$Activate) {

			if(is_array($data)) {

				/**
				 * Array message
				 */
				printf(self::$Intro . " In " . self::$File . " :" . PHP_EOL . PHP_EOL);
				printf(str_pad('Line',$CountLine) . str_pad('Function',$CountFunction) . 'Message'. PHP_EOL);
				printf(str_pad(self::$Line,$CountLine) . str_pad(self::$Function,$CountFunction) . "Array" . PHP_EOL);
				print_r($data);
			} else {

				/**
				 * Text message
				 */
				printf(self::$Intro . " In " . self::$File . " :" . PHP_EOL . PHP_EOL);
				printf(str_pad('Line',$CountLine) . str_pad('Function',$CountFunction) . 'Message'. PHP_EOL);
				printf(str_pad(self::$Line,$CountLine) . str_pad(self::$Function,$CountFunction) . $data . PHP_EOL . PHP_EOL);

			}
		}

	}
} 