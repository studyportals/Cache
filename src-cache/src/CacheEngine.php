<?php
/**
 * @file CacheEngine.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\Cache;

/**
 * CacheEngine.
 *
 * @package StudyPortals.Framework
 */
abstract class CacheEngine implements Cache, \Serializable{

	/**
	 * A toggle for whether this CacheEngine is enabled.
	 *
	 * <p>If <em>false</em>, all data is retrieved fresh (c.q. all calls to
	 * {@link CacheEngine::__get()} will return <em>null</em>.</p>
	 *
	 * @var boolean
	 */

	protected $_enabled = true;

	/**
	 * Toggle the "enabled"-state of the Cache.
	 *
	 * <p>Returns the previous "enabled"-state of the Cache.</p>
	 *
	 * @param boolean $state
	 *
	 * @return boolean
	 */

	public final function enable($state = true){

		$old_state = $this->_enabled;

		$this->_enabled = (bool) $state;

		return $old_state;
	}

	/**
	 * Return the "enabled"-state of the Cache.
	 *
	 * @return boolean
	 * @see CacheEngine::enable()
	 */

	public final function isEnabled(){

		return $this->_enabled;
	}

	/**
	 * Spawn a CacheStore based upon the current CacheEngine.
	 *
	 * @param string $store
	 * @param integer $ttl
	 *
	 * @return CacheStore
	 */

	public final function spawnStore($store, $ttl = 0){

		return new CacheStore($this, $store, $ttl);
	}

	/**
	 * Check if the serialized value is smaller than 2 MB.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */

	protected function validateValueSize($value){

		// Serialize the value being saved to get a byte-stream string.
		// Strlen returns number of bytes in a string.
		$size = strlen(serialize($value));

		// The limit for values is 4 MB.
		$allowed = 1024 * 1024 * 4;
		if($size > $allowed){

			return false;
		}

		return true;
	}

	/**
	 * Flush the cache.
	 *
	 * <p>This method should remove (c.q. invalidate) all entries currently in
	 * the CacheEngine.</p>
	 *
	 * @return bool
	 */

	abstract public function flush();
}