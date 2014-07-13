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
 * @filename Library.Utility.Request.php
 */

namespace Noze\Utility;
use \Noze\Noze;
use \Noze\Packet\Handle;

class Request {


	/**
	 * Function to connect to an host by the method GET and to get the data
	 *
	 * @param string    $url
	 * @param bool      $ic
	 *
	 * @return array|string
	 */
	public static function get($url, $ic = false) {
		$urlp = parse_url($url);
		$fp = fsockopen($urlp['host'], 80);
		$path = explode('/', $url, 4);
		$cp = count($path);

		$path = ($cp >= 4) ? $path[3] : "";
		$req = "GET /$path HTTP/1.0\r\n";
		$req .="Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
		$req .="Accept-Charset:ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
		$req .= "Host: $urlp[host]\r\n";
		$req .= "Accept-Language: en-us,en;q=0.5\r\n";
		$req .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0) Gecko/20100101 Firefox/6.0\r\n";
		$req .= "Connection: close\r\n\r\n";

		fputs($fp, $req);
		stream_set_timeout($fp,4);
		$res = stream_get_contents($fp);
		fclose($fp);

		if($ic) {
			return $res;
		}

		$res = explode("\r\n\r\n",$res,2);

		return $res[1];
	}


	/**
	 * Function to connect to an host by the method POST and to get the data
	 *
	 * @param string $url
	 * @param string $data
	 * @param string $ref
	 *
	 * @return array
	 */
	public static function post($url, $data, $ref=NULL) {
		$url = parse_url($url);
		$http = fsockopen($url['host'], 80, $en, $es, 45);
		$result = '';

		if($http) {

			fputs($http, "POST ".$url['path']." HTTP/1.1\r\n");
			fputs($http, "Host: ".$url['host']."\r\n");

			if(!is_null($ref)) {
				fputs($http, "Referer: ".$ref."\r\n");
			}

			fputs($http, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($http, "Content-length: ".strlen($data)."\r\n");
			fputs($http, "Connection: close\r\n\r\n");
			fputs($http, $data);

			while(!feof($http)&&!is_bool($http)) {

				@$result .= fgets($http, 128);
			}
		} else {
			return array(
				"status" => false,
				"error" => "(".$en.") ".$es
			);
		}
		fclose($http);

		$result = explode("\r\n\r\n", $result, 2);
		$header = (isset($result[0])) ? $result[0] : false;
		$content = (isset($result[1])) ? $result[1] : false;

		return array(
			"status" => true,
			"header" => $header,
			"content" => $content
		);
	}


	/**
	 * Get information about an user by his Id
	 *
	 * @param int $UserId
	 *
	 * @return bool
	 */
	public static function getUser($UserId) {

		$UserId = Handle::parseUserId($UserId);

		if(isset(Noze::$Users[$UserId])) {

			return Noze::$Users[$UserId];
		} else {

			return false;
		}

	}


	/**
	 * Get the Id by the Name or the Name by the Id
	 *
	 * @param int|string $data
	 *
	 * @return string
	 */
	public static function getIdName($data=null) {

		/**
		 * Check if it's an Id or a username
		 */
		if(is_numeric($data)) {

			$url = 'http://xatspace.com/i='.$data;
			$fgc = file_get_contents($url);
			$regex = '!^.+id=([0-9]*)&UserName=([^"]*)".+$!Usi';
			$res = trim(preg_replace($regex, '$2', $fgc));

			return $res;
		} else {

			$url = 'http://xatspace.com/'.$data;
			$fgc = file_get_contents($url);
			$regex = '!^.+id=([0-9]*)&UserName=([^"]*)".+$!Usi';
			$res = trim(preg_replace($regex, '$1', $fgc));

			return $res;
		}
	}
} 