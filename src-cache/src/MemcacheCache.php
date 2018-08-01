<?php

/**
 * @file Engines/Memcache.php
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Danny Peeters <danny@studyportals.eu>
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @copyright © 2010-2016 StudyPortals B.V., all rights reserved.
 * @version 1.2..1
 */

namespace StudyPortals\Cache;

use StudyPortals\Exception\ExceptionHandler;

/**
 * Memcache (PECL/Memcache) based caching-engine.
 *
 * <p>This engine forms a wrapper around PHP/PECL's Memcache class.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage Cache
 */

class MemcacheCache extends CacheEngine{

	protected $_host;
	protected $_port;
	protected $_prefix = '';

	protected $_Memcache;

	/**
	 * Construct a new MemcacheCache.
	 *
	 * @param string $host
	 * @param integer $port
	 * @param string $prefix
	 *
	 * @throws CacheException
	 */

	public function __construct($host, $port = 11211, $prefix = ''){

		$this->_host = (string) $host;
		$this->_port = (int) $port;

		$preg_match = preg_match('/^[a-z0-9]*$/', $prefix);
		if($prefix !== '' && $preg_match !== 1){

			throw new CacheException(
				"Invalid prefix '$prefix': needs to be alphanumeric"
			);
		}

		$this->_prefix = (string) $prefix;

		$this->_Memcache = new \Memcache();

		if(!Memcache::connect($this->_Memcache, $this->_host, $this->_port)){

			throw new CacheException('Failed to connect to Memcache');
		}
	}

	/**
	 * Add an entry to the MemcacheCache.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param integer $ttl
	 * @return boolean
	 * @throws CacheException
	 */

	public function set($name, $value, $ttl = 0){

		$name = trim($name);

		if($name == ''){

			throw new CacheException('Cache-entry name cannot be empty');
		}

		$name = $this->_prependPrefix($name);

		if(is_resource($value)){

			throw new CacheException('Cannot cache values of type "resource"');
		}

		if(!$this->validateValueSize($value)){

			ExceptionHandler::notice("Value in $name is too big to be stored in memcache.");
		}

		// A 30 day+ TTL is interpreted as a timestamp by memcached

		if($ttl > 2592000){

			$ttl = time() + $ttl;
		}

		// Name cannot be longer than 250 characters

		assert('strlen($name) <= 250');
		if(strlen($name) > 250) return false;

		$result = $this->_Memcache->set($name, $value, 0, $ttl);

		if($result === false && !Memcache::isConnected($this->_Memcache)){

			throw new CacheException("Failed to set '$name', Memcache appears
				to be disconnected!?");
		}

		return $result;
	}

	/**
	 * Retrieve an entry from the MemcacheCache.
	 *
	 * @param string $name
	 * @param boolean &$error
	 * @return mixed
	 * @see Cache::get()
	 * @see Memcache::get()
	 */

	public function get($name, &$error = false){

		// If disabled, return nothing so that this cache is overwritten

		if(!$this->_enabled){

			return null;
		}

		$name = $this->_prependPrefix($name);

		error_clear_last();

		$value = @$this->_Memcache->get($name);

		// Attempt to separate "real" errors from missing cache entries

		$last_error = error_get_last();

		if($value === false && $last_error !== null && $last_error['type'] === E_WARNING){

			$error = true;

			return null;
		}

		if($value === false){

			$value = null;
		}

		return $value;
	}

	/**
	 * Delete an entry from the MemcacheCache.
	 *
	 * @param string $name
	 * @return boolean
	 * @see Cache::delete()
	 * @see Memcache::delete()
	 */

	public function delete($name){

		$name = $this->_prependPrefix($name);

		$result = @$this->_Memcache->delete($name);

		return $result;
	}

	/**
	 * Flush the MemcacheCache.
	 *
	 * @return boolean
	 * @see CacheEngine::flush()
	 * @see Memcache::flush()
	 */

	public function flush(){

		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$result = @$this->_Memcache->flush();

		assert('$result !== false');
		return $result;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 * @since 5.1.0
	 */

	public function serialize(){

		return serialize("$this->_host:$this->_port");
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @throws CacheException
	 * @since 5.1.0
	 */

	public function unserialize($serialized){

		list($this->_host, $this->_port) = explode(':', unserialize($serialized));

		$this->_Memcache = new \Memcache();

		if(!Memcache::connect($this->_Memcache, $this->_host, $this->_port)){

			throw new CacheException('Failed to connect to Memcache');
		}
	}

	private function _prependPrefix($name){

		if($this->_prefix !== ''){

			$name = $this->_prefix . ':' . $name;
		}

		return $name;
	}
}