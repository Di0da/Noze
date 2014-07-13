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
 * @filename Library.AutoLoader.php
 */

namespace Noze;


/**
 * AutoLoader will load all required resources.
 *
 * @param string $Class
 *
 * @return true
 */
function __autoload ($Class)
{
	/**
	 * We may only load classes that belong to the Noze framework
	 */
	if (!substr($Class, 0, 3) == 'Noze') {

		return;
	}

	/**
	 * Check where we should find this file.
	 */
	$Exploded = explode('\\', $Class);
	$Exploded[0] = __DIR__;

	/**
	 * Import the file.
	 */
	require_once implode(DIRECTORY_SEPARATOR, $Exploded) . '.php';
}

/**
 * Register with php
 */
spl_autoload_register ('Noze\__autoload');