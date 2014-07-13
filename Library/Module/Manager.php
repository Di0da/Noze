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
 * @filename Library.Module.Manager.php
 */

namespace Noze\Module;
use Noze\Utility\Debug;
use Countable;
use ArrayAccess;
use DirectoryIterator;

class Manager implements ArrayAccess, Countable {

	/**
	 * Location of module files
	 *
	 * @const string
	 */
	const MODULE_DIR = 'Modules';

	/**
	 * Constant that can be returned by modules to indicate to halt noticing further modules
	 *
	 * @const int
	 */
	const STOP = -1;

	/**
	 * Modules that have priority over other modules
	 *
	 * @var array
	 */
	private $priorityList = array();


	/**
	 * Index of loaded modules
	 *
	 * @var array
	 */
	private $Modules = array();


	/**
	 * A list of optional arguments that should be passed every call
	 *
	 * @var array
	 */
	private $prefixArguments = array();


	/**
	 * Constructor, loads all Modules in the Module directory
	 */
	public function __construct(array $Priorities = array()) {

		$this->priorityList = $Priorities;
		$Directory = new DirectoryIterator(self::MODULE_DIR);

		foreach ($Directory as $File) {

			$Filename = $File->getFilename();

			if ($File->isDot() || $File->isDir() || $Filename[0] == '.') {
				// Ignore hidden files and directories
				continue;
			} else if ($File->isFile() && substr($Filename, -4) != '.php') {
				continue;
			} else {
				try {

					$this->load(substr($Filename, 0, -4));
				} catch (Exception $e) {
					/**
					 * Error while loading module
					 */
					Debug::debug('Error while loading module :' .$e->getMessage());
				}
			}
		}

		return;
	}


	/**
	 * Call destructors for each module by closing the linked list.
	 */
	public function __destruct() {

		$this->Plugins = null;
	}


	/**
	 * List of arguments that should be passed on to Modules
	 *
	 * @param array $Optional
	 *
	 * @return bool
	 */
	public function addPrefixArgument(array $Optional) {

		$this->prefixArguments = array_merge($this->prefixArguments, $Optional);

		return true;
	}


	/**
	 * Calls a given method on all modules
	 * with the arguments passed to this method.
	 * The loop will halt when the method returns the HALT constant,
	 * indicating all work is done
	 *
	 * @param string $Method
	 * @param array  $Arguments
	 *
	 * @return bool
	 */
	public function __call($Method, array $Arguments) {

		/**
		 * Add out predefined prefix arguments to the total list
		 */
		$Arguments = array_merge ($this->prefixArguments, $Arguments);

		/**
		 * Loop all loaded Modules
		 */
		foreach ($this->Modules as $Module) {

			/**
			 * Check if the module has the method
			 */
			if (!method_exists($Module ['object'], $Method)) {

				continue;
			}

			/**
			 * Check if we should stop calling modules
			 */
			if (call_user_func_array(array($Module['object'], $Method), $Arguments) === self::STOP) {

				break;
			}
		}

		return true;
	}


	/**
	 * Loads a module into the Framework
	 * and prioritize it according to our priority list
	 *
	 * @param string $Module Filename in the MODULE_DIR we want to load
	 *
	 * @return true
	 */
	public function load($Module) {

		/**
		 * Put the name of the module in lowercase and the first letter in uppercase
		 */
		$Module = ucfirst(strtolower($Module));

		if(isset($this->Modules[$Module])) {

			/**
			 * Return the message AlreadyLoaded
			 */
			return 'AL';
		} elseif(!file_exists(self::MODULE_DIR . DIRECTORY_SEPARATOR . $Module . '.php')) {

			Debug::debug('Class file for ' . $Module . ' could not be found.');

			/**
			 * Return NotFound
			 */
			return 'NF';
		}

		/**
		 * Check if this class already exists.
		 */
		$Path = self::MODULE_DIR . DIRECTORY_SEPARATOR . $Module . '.php';
		$ClassName = '\\Noze\Modules\\' . $Module;

		if (class_exists($ClassName, false)) {

			/**
			 * Check if the user has the runkit extension
			 */
			if (function_exists('runkit_import')) {

				runkit_import ($Path, RUNKIT_IMPORT_OVERRIDE | RUNKIT_IMPORT_CLASSES);
			} else {

				/**
				 * Here, we load the file's contents first, then use preg_replace() to replace the original class-name with a random one.
				 * After that, we create a copy and include it.
				 */
				$NewName   = $Module . '_' . md5 (mt_rand () . time ());
				$ClassName = '\\Noze\\Modules\\' . $NewName;
				$Contents  = preg_replace("/(class[\s]+?)" . $Module . "([\s]+?implements[\s]+?\\\Noze\\\Module\\\ModuleInterface[\s]+?{)/", "\\1" . $NewName . "\\2", file_get_contents($Path));

				$name = tempnam(sys_get_temp_dir(), 'module');
				file_put_contents ($name, $Contents);
				require_once $name;
				unlink ($name);
			}
		} else {
			require_once $Path;
		}

		$ObjectModule = new $ClassName();
		$New = array (
			'object'   => $ObjectModule,
			'loaded'   => time(),
			'name'     => $ClassName,
			'modified' => (isset($Contents) ? true : false)
		);

		/**
		 * Check if this module implements our default interface
		 */
		if(!$ObjectModule instanceof ModuleInterface) {

			Debug::debug('Manager::offsetSet () expects argument 2 to be instance of ModuleInterface.');
		}

		/**
		 * Prioritize
		 */
		if(in_array($Module, $this->priorityList)) {
			/**
			 * So, here we reverse our list of loaded modules, so that prioritized modules will be the last ones,
			 * then, we add the current prioritized modules to the array
			 * and reverse it again.
			 */
			$Temp               = array_reverse($this->Modules, true);
			$Temp[$Module]      = $New;
			$this->Modules      = array_reverse($Temp, true);
		} else {

			$this->Modules[$Module] = $New;
		}

		/**
		 * Return the message Loaded
		 */
		return 'L';
	}


	/**
	 * Unload a module from the Framework
	 *
	 * @param  string $Module
	 *
	 * @return true
	 */
	public function unload($Module) {

		/**
		 * Put the name of the module in lowercase and the first letter in uppercase
		 */
		$Module = ucfirst(strtolower($Module));

		if (!isset($this->Modules[$Module])) {

			/**
			 * Return the message AlreadyUnloaded
			 */
			return 'AU';
		}

		/*
		 * Remove this module, also calling the __destruct method of it.
		 */
		unset($this->Modules[$Module]);

		/**
		 * Return the message Unloaded
		 */
		return 'U';
	}


	/**
	 * Reloads a module by first calling unload and then load
	 *
	 * @param  string $Module
	 *
	 * @return true
	 */
	public function reload($Module) {

		$Unload = $this->unload($Module);

		if($Unload != "U") {
			return $Unload;
		}

		return $this->load($Module);
	}


	/**
	 * Returns the time when a module was loaded or false if we don't have it
	 *
	 * @param string $Module
	 *
	 * @return false|int
	 */
	public function timeLoaded($Module) {
		/**
		 * Put the name of the module in lowercase and the first letter in uppercase
		 */
		$Module = ucfirst(strtolower($Module));

		if (!isset($this->Modules[$Module])) {
			return false;
		}

		return $this->Modules[$Module]['loaded'];
	}


	/**
	 * Returns if a module has been modified or -1 if we do not have it
	 *
	 * @param string $Module
	 *
	 * @return bool
	 */
	public function isModified($Module) {
		/**
		 * Put the name of the module in lowercase and the first letter in uppercase
		 */
		$Module = ucfirst(strtolower($Module));

		if (!isset ($this->Modules[$Module])) {
			return -1;
		}

		return $this->Modules[$Module]['modified'];
	}


	/**
	 * Returns an array with names of all loaded modules, sorted on their priority
	 *
	 * @return array
	 */
	public function getLoadedModules() {

		return array_keys($this->Modules);
	}


	/**
	 * Part of the Countable interface
	 *
	 * @return int
	 */
	public function count() {

		return count($this->Modules);
	}


	/**
	 * Returns instance of a loaded module if we have it, or false if we don't have it
	 *
	 * @param string $Module
	 *
	 * @return bool|object
	 */
	public function offsetGet($Module) {

		if (!isset ($this->Modules[$Module])) {

			return false;
		}

		return $this->Modules[$Module]['object'];
	}


	/**
	 * Check if we have loaded a certain module
	 *
	 * @param string $Module
	 *
	 * @return bool
	 */
	public function offsetExists($Module) {

		return isset ($this->Modules[$Module]);
	}


	/**
	 * Creates a new Module in our list
	 *
	 * @param string $Offset
	 * @param object $Module
	 *
	 * @return true
	 */
	public function offsetSet($Offset, $Module) {

		if (!$Module instanceof ModuleInterface) {

			Debug::debug('Manager::offsetSet () expects argument 2 to be instance of ModuleInterface.');
		}

		$toBeInserted = array (
			'object'    => $Module,
			'loaded'    => time(),
			'name'      => get_class($Module),
			'modified'  => false
		);

		if (in_array($Offset, $this->priorityList)) {

			$Temp               = array_reverse($this->Modules, true);
			$Temp[$Offset]      = $toBeInserted;
			$this->Modules      = array_reverse($Temp, true);
		} else {

			$this->Modules[$Offset] = $toBeInserted;
		}

		return true;
	}


	/**
	 * Unload a Module, this is basically the same as unload()
	 *
	 * @param  string $Module
	 *
	 * @return true
	 */
	public function offsetUnset($Module) {

		if (!isset ($this->Modules[$Module])) {

			return true;
		}

		unset ($this->Modules[$Module]);

		return true;
	}

} 