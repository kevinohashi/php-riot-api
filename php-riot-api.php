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

	private $REGION;	
	//variable to retrieve last response code
	private $responseCode; 


	private static $errorCodes = array(0   => 'NO_RESPONSE',
									   400 => 'BAD_REQUEST',
									   401 => 'UNAUTHORIZED',
									   404 => 'NOT_FOUND',
									   429 => 'RATE_LIMIT_EXCEEDED',
									   500 => 'SERVER_ERROR',
									   503 => 'UNAVAILABLE');




	// Whether or not you want returned queries to be JSON or decoded JSON.
	// honestly I think this should be a public variable initalized in the constructor, but the style before me seems definitely to use const's.
	// Remove this commit if you want. - Ahubers
	const DECODE_ENABLED = TRUE;

	public function __construct($region, CacheInterface $cache = null)
	{
		$this->REGION = $region;

		$this->shortLimitQueue = new SplQueue();
		$this->longLimitQueue = new SplQueue();

		$this->cache = $cache;
	}

	//Returns all champion information.
	public function getChampion(){
		$call = 'champion';

		//add API URL to the call
		$call = self::API_URL_1_2 . $call;

		return $this->request($call);
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
		$call = self::API_URL_2_2  . 'matchhistory/' . $id;
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
			if (self::DECODE_ENABLED) {
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

	private function updateLimitQueue($queue, $interval, $call_limit){
		
		while(!$queue->isEmpty()){
			
			/* Three possibilities here.
			1: There are timestamps outside the window of the interval,
			which means that the requests associated with them were long
			enough ago that they can be removed from the queue.
			2: There have been more calls within the previous interval
			of time than are allowed by the rate limit, in which case
			the program blocks to ensure the rate limit isn't broken.
			3: There are openings in window, more requests are allowed,
			and the program continues.*/

			$timeSinceOldest = time() - $queue->bottom();
			// I recently learned that the "bottom" of the
			// queue is the beginning of the queue. Go figure.

			// Remove timestamps from the queue if they're older than
			// the length of the interval
			if($timeSinceOldest > $interval){
					$queue->dequeue();
			}
			
			// Check to see whether the rate limit would be broken; if so,
			// block for the appropriate amount of time
			elseif($queue->count() >= $call_limit){
				if($timeSinceOldest < $interval){ //order of ops matters
					echo("sleeping for".($interval - $timeSinceOldest + 1)." seconds\n");
					sleep($interval - $timeSinceOldest);
				}
			}
			// Otherwise, pass through and let the program continue.
			else {
				break;
			}
		}

		// Add current timestamp to back of queue; this represents
		// the current request.
		$queue->enqueue(time());
	}

	private function request($call, $otherQueries=false, $static = false) {
				//format the full URL
		$url = $this->format_url($call, $otherQueries);

		//caching
		if($this->cache !== null && $this->cache->has($url)){
			$result = $this->cache->get($url);
		} else {
			// Check rate-limiting queues if this is not a static call.
			if (!$static) {
				$this->updateLimitQueue($this->longLimitQueue, self::LONG_LIMIT_INTERVAL, self::RATE_LIMIT_LONG);
				$this->updateLimitQueue($this->shortLimitQueue, self::SHORT_LIMIT_INTERVAL, self::RATE_LIMIT_SHORT);
			}

			//call the API and return the result
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);			
			$result = curl_exec($ch);
			$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);


			if($this->responseCode == 200) {
				if($this->cache !== null){
					$this->cache->put($url, $result, self::CACHE_LIFETIME_MINUTES * 60);
				}
	        	if (self::DECODE_ENABLED) {
		            $result = json_decode($result, true);
	        	}
			} else {
				throw new Exception(self::$errorCodes[$this->responseCode]);
			}
		}
		return $result;
	}

	//creates a full URL you can query on the API
	private function format_url($call, $otherQueries=false){
		//because sometimes your url looks like .../something/foo?query=blahblah&api_key=dfsdfaefe
		return str_replace('{region}', $this->REGION, $call) . ($otherQueries ? '&' : '?') . 'api_key=' . self::API_KEY;
	}

	public function getLastResponseCode(){
		return $this->responseCode;
	}

	public function debug($message) {
		echo "<pre>";
		print_r($message);
		echo "</pre>";
	}
}
