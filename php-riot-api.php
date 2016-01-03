<?php
/*

PHP Riot API 
Kevin Ohashi (http://kevinohashi.com)
http://github.com/kevinohashi/php-riot-api


The MIT License (MIT)

Copyright (c) 2013 Kevin Ohashi

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once('CacheInterface.php');
require_once('NullCache.php');
require_once('RateLimitHandler.php');
require_once('RateLimitSleeper.php');

class riotapi {
	const API_URL_1_1 = 'https://{region}.api.pvp.net/api/lol/{region}/v1.1/';
	const API_URL_1_2 = 'https://{region}.api.pvp.net/api/lol/{region}/v1.2/';
	const API_URL_1_3 = 'https://{region}.api.pvp.net/api/lol/{region}/v1.3/';
	const API_URL_1_4 = 'https://{region}.api.pvp.net/api/lol/{region}/v1.4/';
	const API_URL_2_1 = 'https://{region}.api.pvp.net/api/lol/{region}/v2.1/';
	const API_URL_2_2 = 'https://{region}.api.pvp.net/api/lol/{region}/v2.2/';
	const API_URL_2_3 = "https://{region}.api.pvp.net/api/lol/{region}/v2.3/";
	const API_URL_2_4 = "https://{region}.api.pvp.net/api/lol/{region}/v2.4/";
	const API_URL_2_5 = "https://{region}.api.pvp.net/api/lol/{region}/v2.5/";
	const API_URL_STATIC_1_2 = 'https://global.api.pvp.net/api/lol/static-data/{region}/v1.2/';
	const API_URL_CURRENT_GAME_1_0 = 'https://{region}.api.pvp.net/observer-mode/rest/consumer/getSpectatorGameInfo/';

	const HTTP_OK = 200;
	const HTTP_RATE_LIMIT = 429;

	const API_KEY = 'INSERT_API_KEY_HERE';

	// Rate limit for 10 minutes
	const LONG_LIMIT_INTERVAL = 600;
	const RATE_LIMIT_LONG = 500;

	// Rate limit for 10 seconds'
	const SHORT_LIMIT_INTERVAL = 10;
	const RATE_LIMIT_SHORT = 10;

	// Cache variables
	const CACHE_LIFETIME_MINUTES = 60;

	private $cache;
	private $rateLimitHandler;

	private $REGION;	
	//variable to retrieve last response code
	private $responseCode;
	private $responseHeaders;
	private $responseBody;


	private static $errorCodes = array(0   => 'NO_RESPONSE',
									   400 => 'BAD_REQUEST',
									   401 => 'UNAUTHORIZED',
									   403 => 'ACCESS_DENIED',
									   404 => 'NOT_FOUND',
									   429 => 'RATE_LIMIT_EXCEEDED',
									   500 => 'SERVER_ERROR',
									   503 => 'UNAVAILABLE');


	// Whether or not you want returned queries to be JSON or decoded JSON.
	// honestly I think this should be a public variable initalized in the constructor, but the style before me seems definitely to use const's.
	// Remove this commit if you want. - Ahubers
	const DECODE_ENABLED = TRUE;

	public function __construct($region, CacheInterface $cache = null, RateLimitHandler $rateLimitHandler = null)
	{
		$this->REGION = $region;

		// if a cache and rate limiter weren't provided, then we'll just use these default ones
		$this->cache = $cache = $cache !== null ? $cache : new NullCache();
		$this->rateLimitHandler = $rateLimitHandler !== null ? $rateLimitHandler : new RateLimitSleeper();
	}

	//Returns all champion information.
	public function getChampion(){
		$call = 'champion';

		//add API URL to the call
		$call = self::API_URL_1_2 . $call;

		return $this->request($call);
	}

	// Returns all free champions.
	public function getFreeChampions()
	{
		$call  = 'champion?freeToPlay=true';
		$call  = self::API_URL_1_2 . $call;

		return $this->request($call, true);
	}

	//gets current game information for player on platform (region?)
	//platform seems to be just uppercase region and 1 afterwards right now.
	public function getCurrentGame($id,$platform){
		$call = self::API_URL_CURRENT_GAME_1_0 . $platform . '/' . $id;
		return $this->request($call);
	}

	//performs a static call. Not counted in rate limit.
	public function getStatic($call=null, $id=null) {
		$call = self::API_URL_STATIC_1_2 . $call . "/" . $id;
		
		return $this->request($call, (strpos($call,'?') !== false), true);
	}

	//New to my knowledge. Returns match details.
	public function getMatch($matchId) {
		$call = self::API_URL_2_2  . 'match/' . $matchId;
		return $this->request($call);
	}

	//Returns a user's matchHistory given their summoner id.
	public function getMatchHistory($id) {
		$call = self::API_URL_2_2  . 'matchlist/by-summoner/' . $id;
		return $this->request($call);
	}

	//Returns game statistics given a summoner's id.
	public function getGame($id){
		$call = 'game/by-summoner/' . $id . '/recent';

		//add API URL to the call
		$call = self::API_URL_1_3 . $call;

		return $this->request($call);
	}

	//Returns the league of a given summoner.
	public function getLeague($id, $entry=null){
		$call = 'league/by-summoner/' . $id . "/" . $entry;

		//add API URL to the call
		$call = self::API_URL_2_5 . $call;

		return $this->request($call);
	}

	//Returns league information given a *list* of teams.
	public function getLeagueByTeam($ids){
		$call = 'league/by-team/';
		if (is_array($ids)) {
			$call .= implode(",", $ids);
		}
		else {
			$call .= $ids;
		}
		//add API URL to the call
		$call = self::API_URL_2_5 . $call;
		return $this->request($call);
	}

	//Returns the challenger ladder.
	public function getChallenger() {
		$call = 'league/challenger?type=RANKED_SOLO_5x5';

		//add API URL to the call
		$call = self::API_URL_2_5 . $call;
		return $this->request($call, true);
	}

	//Returns a summoner's stats given summoner id.
	public function getStats($id,$option='summary'){
		$call = 'stats/by-summoner/' . $id . '/' . $option;

		//add API URL to the call
		$call = self::API_URL_1_3 . $call;

		return $this->request($call);
	}
	
	//returns a summoner's id
	public function getSummonerId($name) {
			$name = strtolower($name);
			$summoner = $this->getSummonerByName($name);
			if (!self::DECODE_ENABLED) {
				return $summoner[$name]['id'];
			}
			else {
				$summoner = json_decode($summoner, true);
				return $summoner[$name]['id'];
			}
	}		

	//Returns summoner info given summoner id.
	public function getSummoner($id,$option=null){
		$call = 'summoner/' . $id;
		switch ($option) {
			case 'masteries':
				$call .= '/masteries';
				break;
			case 'runes':
				$call .= '/runes';
				break;
			case 'name':
				$call .= '/name';
				break;

			default:
				//do nothing
				break;
		}

		//add API URL to the call
		$call = self::API_URL_1_4 . $call;

		return $this->request($call);
	}

	//Gets a summoner's info given their name, instead of id.
	public function getSummonerByName($name){
		//use rawurlencode for special characters
		$call = 'summoner/by-name/' . rawurlencode($name);

		//add API URL to the call
		$call = self::API_URL_1_4 . $call;

		return $this->request($call);
	}

	//Gets the teams of a summoner, given summoner id.
	public function getTeam($id){
		$call = 'team/by-summoner/' . $id;

		//add API URL to the call
		$call = self::API_URL_2_3 . $call;

		return $this->request($call);
	}

	public function getLastResponseHeaders(){
		return $this->responseHeaders;
	}

	public function getLastResponseBody(){
		return $this->responseBody;
	}

	public function getLastResponseCode(){
		return $this->responseCode;
	}

	private function request($call, $otherQueries=false, $static = false) {
				//format the full URL
		$url = $this->format_url($call, $otherQueries);

		$result = $this->cache->remember($url, self::CACHE_LIFETIME_MINUTES * 60, function () use ($url, $call, $otherQueries, $static)
		{
			$this->curlExecute($url);

			/**
			 * Here we are going to check if we were rate limited. If we WERE rate limited, then lets call our rate limit
			 * handler and let that class deal with it.
			 */
			if ($this->responseCode == self::HTTP_RATE_LIMIT) {
				$retryAfter = (int) $this->responseHeaders['Retry-After'];
				$this->rateLimitHandler->handleLimit($retryAfter);

				if ($this->rateLimitHandler->retryEnabled()) {
					return $this->request($call, $otherQueries, $static);
				}
			}

			if ($this->responseCode != self::HTTP_OK) {
				throw new Exception(self::$errorCodes[$this->responseCode]);
			}

			$result = $this->responseBody;
			if (self::DECODE_ENABLED) {
				$result = json_decode($result, true);
			}

			return $result;
		});

		return $result;
	}

	private function curlExecute($url){
		//call the API and return the result
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$result = curl_exec($ch);
		list($header, $body) = explode("\r\n\r\n", $result, 2);
		$this->responseHeaders = $this->parseHeaders($header);
		$this->responseBody = $body;
		$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
	}

	//creates a full URL you can query on the API
	private function format_url($call, $otherQueries=false){
		//because sometimes your url looks like .../something/foo?query=blahblah&api_key=dfsdfaefe
		return str_replace('{region}', $this->REGION, $call) . ($otherQueries ? '&' : '?') . 'api_key=' . self::API_KEY;
	}

	private function parseHeaders($header) {
		$headers = array();
		$headerLines = explode("\r\n", $header);
		foreach ($headerLines as $headerLine) {
			@list($key, $val) = explode(': ', $headerLine, 2);
			$headers[$key] = $val;
		}
		return $headers;
	}

	public function debug($message) {
		echo "<pre>";
		print_r($message);
		echo "</pre>";
	}
}
