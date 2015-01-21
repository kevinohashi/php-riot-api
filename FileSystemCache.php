<?php
/**
 * Created by PhpStorm.
 * User: Chad
 * Date: 1/21/2015
 * Time: 4:48 AM
 */

class FileSystemCache implements CacheInterface {

	/**
	 * @param string $key Check if the cache contains data for the specified key
	 * @return bool
	 */
	public function has($key)
	{
		$entry = $this->load($key);

		return $this->expired($entry);
	}

	/**
	 * @param string $key Gets data for specified key
	 * @return string|null
	 */
	public function get($key)
	{
		$entry = $this->load($key);

		if ($this->expired($entry))
			$data = null;

		return $entry->data;
	}

	/**
	 * @param string $key
	 * @param $data
	 * @param int $ttl Time for the data to live inside the cache
	 * @return mixed
	 */
	public function put($key, $data, $ttl = 0)
	{
		$this->store($key, $data, $ttl, time());
	}

	private function load($key)
	{
		return json_decode(file_get_contents($this->hash($key)));
	}

	private function store($key, $data, $ttl, $createdAt)
	{
		$entry = [
			'createdAt' => $createdAt,
			'ttl' => $ttl,
			'data' => $data
		];

		file_put_contents($this->hash($key), json_encode($entry));
	}

	private function expired($entry)
	{
		return (time() + $entry->ttl) > $entry->createdAt;
	}

	private function hash($key)
	{
		return md5($key);
	}

}