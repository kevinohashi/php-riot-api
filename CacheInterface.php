<?php

interface CacheInterface {

	/**
	 * @param string $key Checks whether or not the cache contains unexpired data for the specified key
	 * @param boolean $forceClear force the data to be refreshed - new structure static data limit
	 * @return bool
	 */
	public function has($key, $forceClear);

	/**
	 * @param string $key Gets data for specified key
	 * @param boolean $forceClear force the data to be refreshed - new structure static data limit
	 * @return string|null Returns null if the cached item doesn't exist or has expired
	 */
	public function get($key, $forceClear);

	/**
	 * @param string $key
	 * @param $data
	 * @param int $ttl Time in seconds before the data becomes expired
	 * @param boolean $static if the data is static or not - new structure static data limit
	 * @return mixed
	 */
	public function put($key, $data, $ttl = 0, $static);

}