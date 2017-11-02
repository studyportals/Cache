<?php

/**
 * @file Cache/Engines/Cache.php
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Danny Peeters <danny@studyportals.eu>
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @copyright © 2010-2016 StudyPortals B.V., all rights reserved.
 * @version 1.1.3
 */

namespace StudyPortals\Cache;

use StudyPortals\Exception\ExceptionHandler;

/**
 * Simple file-based caching-engine.
 *
 * <p>The FileCache provides a simple file-based caching mechanism. A file is
 * created for each entry in the cache. The file modification time is used as
 * a method to keep track of cache-entry expiry time.</p>
 *
 * @package StudyPortals.Framework
 */

class FileCache extends CacheEngine{

	const CLEAR_INTERVAL = 100;
	const TTL_FUTURE = 31536000;

	protected $_cache_path;

	/**
	 * Construct a new FileCache.
	 *
	 * @param string $cache_path
	 *
	 * @throws CacheException
	 */

	public function __construct($cache_path){

		$this->_cache_path = rtrim($cache_path, '/\\') . '/';

		if(!is_writable($this->_cache_path)){

			throw new CacheException('File cache directory is not writable');
		}
	}

	/**
	 * Get a cache-entry filename based upon the string provided.
	 *
	 * @param string $name
	 * @return string
	 * @throws CacheException
	 */

	protected function _getFileName($name){

		$name = trim($name);

		$name = iconv('ISO-8859-1', 'ASCII//TRANSLIT', $name);
		$name = preg_replace('/[^a-z0-9\-:]+/i', '_', $name);
		$name = str_replace(':', '.', $name);

		if($name == ''){

			throw new CacheException('Cache-entry name cannot be empty');
		}

		return "{$name}.cache";
	}

	/**
	 * Clear expired cache-entries from the cache-path.
	 *
	 * <p>The optional parameter {@link $time} can be used to provide a
	 * reference time for the expiry. When omitted, the current time is
	 * used.</p>
	 *
	 * <p>Returns the number of expired items cleared from the cache.</p>
	 *
	 * @param integer $time
	 * @return integer
	 */

	protected function _clearExpired($time = null){

		if($time === null) $time = time();

		$files = glob("{$this->_cache_path}*.cache");
		$cleared = 0;

		if(is_array($files)){

			foreach($files as $file){

				$path = $this->_cache_path . basename($file);
				$mtime = @filemtime($path);

				// Remove expired files

				if($mtime !== false && $time > $mtime){

					@unlink($path);
					++$cleared;
				}
			}
		}

		return $cleared;
	}

	/**
	 * Add an entry to the FileCache.
	 *
	 * <p>Due to the way FileCache keeps track of expiry dates, setting {@link
	 * $ttl} to zero will set the expiry time to FileCache::TTL_FUTURE which by
	 * default is set to 365 days in the future.</p>
	 *
	 * <p>Every successful "set" has a chance of triggering {@link
	 * FileCache::_clearExpired()} which clears expired entries from the
	 * cache-path. The chance of trigging this method is controlled through the
	 * {@link FileCache::CLEAR_INTERVAL} constant. By default it will be
	 * triggered once every 50 sets.</p>
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param integer $ttl
	 * @return boolean
	 * @throws CacheException
	 * @see Cache::set()
	 * @see FileCache::_clearExpired()
	 */

	public function set($name, $value, $ttl = 0){

		if($ttl <= 0) $ttl = self::TTL_FUTURE;

		if(is_resource($value)){

			throw new CacheException('Cannot cache values of type "resource"');
		}

		if(!$this->validateValueSize($value)){

			ExceptionHandler::notice("Value in $name is too big to be stored in file cache.");
		}

		$file = $this->_getFileName($name);

		$fp = @fopen($this->_cache_path . $file, 'wb');

		if(is_resource($fp)){

			$result = @fwrite($fp, serialize($value));
			$result = ($result && @fclose($fp));
			$result =
				($result && @touch($this->_cache_path . $file, time() + $ttl));

			// Clear expired cache-files every once-in-a-while

			if($result && rand(1, self::CLEAR_INTERVAL) == self::CLEAR_INTERVAL){

				$this->_clearExpired();


			}

			return $result;
		}
		else{

			return false;
		}
	}

	/**
	 * Get an entry from the FileCache.
	 *
	 * @param string $name
	 * @param boolean &$error
	 * @return mixed
	 * @see Cache::get()
	 */

	public function get($name, &$error = false){

		// If disabled, return nothing so that this cache is overwritten

		if(!$this->_enabled){

			return null;
		}

		try{

			$file = $this->_getFileName($name);
		}
		catch(CacheException $e){

			$error = true;

			return null;
		}

		if(time() > @filemtime($this->_cache_path . $file)){

			return null;
		}

		$contents = @file_get_contents($this->_cache_path . $file);

		if($contents !== false){

			error_clear_last();

			$value = @unserialize($contents);

			// Attempt to separate serialised "false" from unserialisation errors

			$last_error = error_get_last();

			if($value === false && $last_error !== null && $last_error['type'] === E_WARNING){

				$error = true;

				return null;
			}

			return $value;
		}

		return null;
	}

	/**
	 * Delete an entry from the FileCache.
	 *
	 * @param string $name
	 * @return boolean
	 * @see Cache::delete()
	 */

	public function delete($name){

		try{

			$file = $this->_getFileName($name);
		}
		catch(CacheException $e){

			return false;
		}

		return @unlink($this->_cache_path . $file);
	}

	/**
	 * Flush the FileCache.
	 *
	 * @return boolean
	 * @see CacheEngine::flush()
	 */

	public function flush(){

		$this->_clearExpired(time() + self::TTL_FUTURE + 1);

		return true;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 * @since 5.1.0
	 */

	public function serialize(){

		return serialize($this->_cache_path);
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @return void
	 * @since 5.1.0
	 */

	public function unserialize($serialized){

		$this->_cache_path = unserialize($serialized);
	}
}