<?php
/**
 * @file Memcache.php
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @version 1.0.0
 * @copyright © 2014 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\Cache;

/**
 * Memcache utility methods.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class Memcache{

	const COMPRESS_THRESHOLD	= 20000;
	const COMPRESS_SAVINGS		= 0.2;

	/**
	 * Connect a Memcache-instance.
	 *
	 * <p>The connection-state of the provided {@link $Memcache}-instance is
	 * <strong>not</strong> checked before attempting to connect it. So, passing
	 * in an already connected instance is not recommended and will most likely
	 * lead to unexpected results.</p>
	 *
	 * @param \Memcache $Memcache
	 * @param string $host
	 * @param integer $port
	 * @param boolean $persistent
	 * @return boolean
	 */

	static public function connect(
		\Memcache $Memcache, $host, $port = 11211, $persistent = true){

		$host = (string) $host;
		$port = (int) $port;

		if($persistent){

			$result = @$Memcache->pconnect($host, $port);
		}
		else{

			$result = @$Memcache->connect($host, $port);
		}

		if($result && self::COMPRESS_THRESHOLD > 0){

			/** @noinspection PhpVoidFunctionResultUsedInspection */
			/** @noinspection PhpUnusedLocalVariableInspection */
			$compress = @$Memcache->setcompressthreshold(
				self::COMPRESS_THRESHOLD, self::COMPRESS_SAVINGS);

			assert('$compress !== false');
		}

		return $result;
	}

	/**
	 * Check if the provided Memcache-instance is connected.
	 *
	 * <p>As there is not built-in functionality in the PHP Memcache class to
	 * check if an instance is connected we use a simple workaround by querying
	 * the version of the (connected) Memcache server. If we fail to get a
	 * version it's (relatively) safe to assume the instance is not
	 * connected.</p>
	 *
	 * @param \Memcache $Memcache
	 * @return bool
	 */

	static public function isConnected(\Memcache $Memcache){

		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$version = @$Memcache->getVersion();

		if($version === false){

			return false;
		}

		return true;
	}
}
