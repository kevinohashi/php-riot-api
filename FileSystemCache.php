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
	 * @param boolean $forceClear force the data to be refreshed - new structure static data limit
	 * @return bool
	 */
	public function has($key, $forceClear)
	{
		if ( ! file_exists($this->getPath($key)))
			return false;

		$entry = $this->load($key);
		return !$this->expired($entry, $forceClear);
	}

	/**
	 * @param string $key Gets data for specified key
	 * @param boolean $forceClear force the data to be refreshed - new structure static data limit
	 * @return string|null
	 */
	public function get($key, $forceClear)
	{
		$entry = $this->load($key);

		$data = null;

		if ( ! $this->expired($entry, $forceClear))
			$data = $entry->data;

		return $data;
	}

	/**
	 * @param string $key
	 * @param $data
	 * @param int $ttl Time for the data to live inside the cache
	 * @param boolean $static if the data is static or not - new structure static data limit
	 * @return mixed
	 */
	public function put($key, $data, $ttl = 0, $static)
	{
		$this->store($key, $data, $ttl, time(), $static);
	}

	private function load($key)
	{
		return json_decode(file_get_contents($this->getPath($key)));
	}

	private function store($key, $data, $ttl, $createdAt, $static)
	{
		$entry = array(
			'createdAt' => $createdAt,
			'ttl' => $ttl,
			'data' => $data,
			'static' => $static
		);

		file_put_contents($this->getPath($key), json_encode($entry));
	}

	private function getPath($key)
	{
		return $this->directory . $this->hash($key);
	}

	private function expired($entry, $forceClear)
	{
		return $entry === null || (time() >= ($entry->createdAt + $entry->ttl) && !$entry->static) || $forceClear;
	}

	private function hash($key)
	{
		return md5($key);
	}

}