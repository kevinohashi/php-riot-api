<?php
require_once('CacheInterface.php');

class FileSystemCache implements CacheInterface {

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @param string $directory Caching directory
	 */
	public function __construct($directory)
	{
		$this->directory = trim($directory, '/\\') . '/';

		if ( ! file_exists($this->directory))
			mkdir($this->directory, 0777, true);
	}

	/**
	 * @param string $key Check if the cache contains data for the specified key
	 * @return bool
	 */
	public function has($key)
	{
		$entry = $this->load($key);
		return !$this->expired($entry);
	}

	/**
	 * @param string $key Gets data for specified key
	 * @return string|null
	 */
	public function get($key)
	{
		$entry = $this->load($key);

		$data = null;

		if ( ! $this->expired($entry))
			$data = $entry->data;

		return $data;
	}

	/**
	 * @param string $key
	 * @param $data
	 * @param int $ttl Time for the data to live inside the cache
	 */
	public function put($key, $data, $ttl = 0)
	{
		$this->store($key, $data, $ttl, time());
	}

	/**
	 * @param $key
	 * @param $ttl
	 * @param $callback
	 * @return mixed
	 */
	public function remember($key, $ttl, $callback)
	{
		$entry = $this->load($key);

		if ( ! $this->expired($entry)) {
			$data = $entry->data;
		} else {
			$data = $callback();
			$this->put($key, $data, $ttl);
		}

		return $data;
	}

	private function load($key)
	{
		$path = $this->getPath($key);

		if ( ! file_exists($path))
			return null;

		return json_decode(file_get_contents($path));
	}

	private function store($key, $data, $ttl, $createdAt)
	{
		$entry = array(
			'createdAt' => $createdAt,
			'ttl' => $ttl,
			'data' => $data
		);

		file_put_contents($this->getPath($key), json_encode($entry));
	}

	private function getPath($key)
	{
		return $this->directory . $this->hash($key);
	}

	private function expired($entry)
	{
		return $entry === null || time() >= ($entry->createdAt + $entry->ttl);
	}

	private function hash($key)
	{
		return md5($key);
	}

}