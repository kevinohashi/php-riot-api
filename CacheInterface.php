<?php

interface CacheInterface {

	/**
	 * @param string $key Check if the cache contains data for the specified key
	 * @return bool
	 */
	public function has($key);

	/**
	 * @param string $key Gets data for specified key
	 * @return string|null Returns null if the cache has expired or non-existing data
	 */
	public function get($key);

	/**
	 * @param string $key
	 * @param $data
	 * @param int $ttl Time for the data to live inside the cache
	 * @return mixed
	 */
	public function put($key, $data, $ttl = 0);

}