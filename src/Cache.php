<?php

/**
 * @file Cache/Cache.php
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Danny Peeters <danny@studyportals.eu>
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @copyright © 2010-2016 StudyPortals B.V., all rights reserved.
 * @version 1.1.5
 */

namespace StudyPortals\Cache;

/**
 * Basic interface for all caching engines.
 *
 * @package StudyPortals.Framework
 */

interface Cache{

	const HOUR 		= 3600;
	const HALFDAY 	= 43200;
	const DAY 		= 86400;

	/**
	 * Cache duration of a week.
	 *
	 * <p>Since we have a weekly release cycle, "week" should actually mean
	 * "until the next launch". So, the number below is actually 8 days.</p>
	 *
	 * @var int
	 */

	const WEEK 		= 691200;

	/**
	 * Return the "enabled"-state of the Cache.
	 *
	 * @return boolean
	 */

	public function isEnabled();

	/**
	 * Add an entry to the cache.
	 *
	 * <p>Different caching engines might impose limitations upon the type
	 * of values set and the names used. The {@link $ttl} value indicates the
	 * time-to-live of the cache-entry in seconds. Set to zero (default) to
	 * make the entry never expire. Again, different caching engines might
	 * interpret "never" in another manner.</p>
	 *
	 * <p>In case an unrecoverable error occurs while trying to set a
	 * cache-entry, the implementer should throw a CacheException.</p>
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param integer $ttl seconds
	 * @return boolean
	 * @throws CacheException
	 */

	public function set($name, $value, $ttl = 0);

	/**
	 * Retrieve an entry from the cache.
	 *
	 * <p>When no matching entry is found in the cache, engines are expected
	 * to return <em>null</em>.</p>
	 *
	 * <p>The optional, pass-by-reference, parameter {@link $error} is used to
	 * signal an error has occurred while retrieving the entry. Hence, the actual
	 * value retrieved from the cache could have been set to <em>false</em>
	 * explicitly. This method should never throw an exception.</p>
	 *
	 * @param string $name
	 * @param boolean $error
	 * @return mixed
	 */

	public function get($name, &$error = false);

	/**
	 * Delete an entry from the cache.
	 *
	 * <p>This method should never throw an exception.</p>
	 *
	 * @param string $name
	 * @return boolean
	 */

	public function delete($name);
}
