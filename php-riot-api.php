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
	
	const API_URL_PLATFORM_3 = "https://{platform}.api.riotgames.com/lol/platform/v3/";
	const API_URL_CHAMPION_MASTERY_3 = "https://{platform}.api.riotgames.com/lol/champion-mastery/v3/";
	const API_URL_SPECTATOR_3 = 'https://{platform}.api.riotgames.com/lol/spectator/v3/';
	const API_URL_STATIC_3 = 'https://{platform}.api.riotgames.com/lol/static-data/v3/';
	const API_URL_MATCH_3 = 'https://{platform}.api.riotgames.com/lol/match/v3/';
	const API_URL_LEAGUE_3 = 'https://{platform}.api.riotgames.com/lol/league/v3/';
	const API_URL_SUMMONER_3 = 'https://{platform}.api.riotgames.com/lol/summoner/v3/';


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

	private $PLATFORM;	
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

	public function __construct($platform, CacheInterface $cache = null)
	{
		$this->PLATFORM = $platform;

		$this->shortLimitQueue = new SplQueue();
		$this->longLimitQueue = new SplQueue();

		$this->cache = $cache;
	}

	//Returns all champion information.
	//Set $free at true to get only free champions.
	public function getChampion($free = false){
		$call = 'champions';
		
		if($free)
			$call  .= '?freeToPlay=true';

		//add API URL to the call
		$call = self::API_URL_PLATFORM_3 . $call;

		return $this->request($call);
	}

	//Returns all free champion information.
	public function getFreeChampions(){
		$call = 'champions?freeToPlay=true';

		//add API URL to the call
		$call = self::API_URL_PLATFORM_3 . $call;

		return $this->request($call);
	}
	
	//Returns all champions mastery for a player
	public function getChampionMastery($id, $championId = false){
		
		$call = self::API_URL_CHAMPION_MASTERY_3 . 'champion-masteries/by-summoner/' . $id ;
		
		if($championId)
			$call .= "/by-champion/" . $championId;
		
		return $this->request($call);
	}

	//gets current game information for player
	public function getCurrentGame($id){
		$call = self::API_URL_SPECTATOR_3 . 'active-games/by-summoner/' . $id;
		return $this->request($call);
	}

	//performs a static call. Not counted in rate limit.
	//$call is what is asked (champion, item...)
	//$id the id for a specific item, champion. Set at null to get all champions, items...
	//$params is the string you get after the "?"
	//		getStatic("champions", 1, "locale=fr_FR&tags=image&tags=spells") will get you image data and spells data in French from champion whose ID is 1, here Annie.
	public function getStatic($call, $id = null, $params = null) {
		$call = self::API_URL_STATIC_3 . $call;
		
		if( $id !=null)
			$call.="/" . $id;
		
		if( $params !=null)
			$call.="?" . $params;
		
		return $this->request($call, true);
	}

	//New to my knowledge. Returns match details.
	//Now that timeline is a separated call, when includedTimeline is true, two calls are done at the same time.
	//Data is then processed to match the old structure, with timeline data included in the match data
	//UPDATE : you can now pass an accountId to know which participant is the player, even in normale game
	public function getMatch($matchId, $accountId = false, $includeTimeline = true) {
		$call = self::API_URL_MATCH_3  . 'matches/' . $matchId;

		if($accountId)
			$call .= "?forAccountId=" . $accountId;
		
		if(!$includeTimeline)
			return $this->request($call);
		
		else
			$timelineCall =  self::API_URL_MATCH_3  . 'timelines/by-match/' . $matchId;
			$data = $this->requestMultiple(array(
				"data"=>$call,
				"timeline"=>$timelineCall
				));
			$data["data"]["timeline"] = $data["timeline"];
			return $data["data"];
	}
	
	//Returns timeline of a match
	public function getTimeline($matchId){
		$call =  self::API_URL_MATCH_3  . 'timelines/by-match/' . $matchId;
		
		return $this->request($call);
	}

	//Returns a user's matchList given their account id.
	public function getMatchList($accountId,$params=null) {
		if($params==null){
			$call = self::API_URL_MATCH_3  . 'matchlists/by-account/' . $accountId;
		}else{
			$call = self::API_URL_MATCH_3  . 'matchlists/by-account/' . $accountId .'?';
			
			//You can pass params either as an array or as string
			if(is_array($params))
				foreach($params as $key=>$param){
					//each param can also be an array, a list of champions, queues or seasons
					//refer to API doc to get details about params
					if(is_array($param))
						foreach($param as $p)
							$call .= $key . '=' . $p . '&';
							
					else
						$call .= $key . '=' . $param . '&';
				}

			else
				$call .= $params . '&';
		}
		
		return $this->request($call);
	}
	
	//Returns a user's recent matchList given their account id.
	public function getRecentMatchList($accountId) {
		$call = self::API_URL_MATCH_3  . 'matchlists/by-account/' . $accountId . '/recent';
		
		return $this->request($call);
	}

	//Returns the league of a given summoner.
	public function getLeague($id){
		$call = 'leagues/by-summoner/' . $id;

		//add API URL to the call
		$call = self::API_URL_LEAGUE_3 . $call;

		return $this->request($call);
	}
	
	//Returns the league position of a given summoner.
	//Similar to the old league /entry
	public function getLeaguePosition($id){
		$call = 'positions/by-summoner/' . $id;

		//add API URL to the call
		$call = self::API_URL_LEAGUE_3 . $call;

		return $this->request($call);
	}

	//Returns the challenger ladder.
	public function getChallenger($queue = "RANKED_SOLO_5x5") {
		$call = 'challengerleagues/by-queue/' . $queue;

		//add API URL to the call
		$call = self::API_URL_LEAGUE_3 . $call;
		return $this->request($call);
	}
	
	//Returns the master ladder.
	public function getMaster($queue = "RANKED_SOLO_5x5") {
		$call = 'masterleagues/by-queue/' . $queue;

		//add API URL to the call
		$call = self::API_URL_LEAGUE_3 . $call;
		return $this->request($call);
	}
	
	//returns a summoner's id
	public function getSummonerId($name) {
			$name = strtolower($name);
			$summoner = $this->getSummonerByName($name);
			if (self::DECODE_ENABLED) {
				return $summoner['id'];
			}
			else {
				$summoner = json_decode($summoner, true);
				return $summoner['id'];
			}
	}		
	
	//returns an account id
	public function getSummonerAccountId($name) {
			$name = strtolower($name);
			$summoner = $this->getSummonerByName($name);
			if (self::DECODE_ENABLED) {
				return $summoner['accountId'];
			}
			else {
				$summoner = json_decode($summoner, true);
				return $summoner['accountId'];
			}
	}		

	//Returns summoner info given summoner id or account id.
	public function getSummoner($id,$accountId = false){
		$call = 'summoners/';
		if ($accountId) {
			$call .= 'by-account/';
		}
		$call .= $id;
		
		//add API URL to the call
		$call = self::API_URL_SUMMONER_3 . $call;

		return $this->request($call);
	}

	//Gets a summoner's info given their name, instead of id.
	public function getSummonerByName($name){
		$call = 'summoners/by-name/' . rawurlencode($name);
		
		//add API URL to the call
		$call = self::API_URL_SUMMONER_3 . $call;

		return $this->request($call);
	}

	//Gets a summoner's masteries.
	public function getMasteries($id){
		$call = 'masteries/by-summoner/' . $id;
		
		//add API URL to the call
		$call = self::API_URL_PLATFORM_3 . $call;

		return $this->request($call);
	}

	//Gets a summoner's runes.
	public function getRunes($id){
		$call = 'runes/by-summoner/' . $id;
		
		//add API URL to the call
		$call = self::API_URL_PLATFORM_3 . $call;

		return $this->request($call);
	}

	//Gets data of matches, given array of id.
	//UPDATE : you can now pass an accountId to know which participant is the player, even in normale game
	public function getMatches($ids, $accountId = false, $includeTimeline = true){
		
		$calls=array();
		
		foreach($ids as $matchId){
			$call = self::API_URL_MATCH_3  . 'matches/' . $matchId;

			if($accountId)
				$call .= "?forAccountId=" . $accountId;
			
			$calls["match-".$matchId] = $call;
			
			if($includeTimeline)
				$calls["timeline-".$matchId] = self::API_URL_MATCH_3  . 'timelines/by-match/' . $matchId;
		}
		
		if(!$includeTimeline)
			return $this->requestMultiple($calls);
		
		$results = array();
		
		$data = $this->requestMultiple($calls);
		
		foreach($data as $k=>$d){
			$e = explode("-", $k);
			
			//Check if it's match data
			if($e[0]=="match"){
				//Check if the timeline exists
				//Timeline is only stored by Riot for one year, too old games may not have it
				if(isset($data["timeline-".$e[1]]["frames"]))
					//add the matching timeline
					$d["timeline"] = $data["timeline-".$e[1]];
				array_push($results, $d);
			}
		}
		
		return $results;
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

	private function request($call, $static = false) {
				//format the full URL
				
		$url = $this->format_url($call);
		//echo $url;
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
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-Riot-Token: '. self::API_KEY
				));			
			$result = curl_exec($ch);
			$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);


			if($this->responseCode == 200) {
				if($this->cache !== null){
					$this->cache->put($url, $result, self::CACHE_LIFETIME_MINUTES * 60);
				}
			} else {
				throw new Exception(self::$errorCodes[$this->responseCode]);
			}
		}
		if (self::DECODE_ENABLED) {
			$result = json_decode($result, true);
		}
		return $result;
	}
	
	private function requestMultiple($calls, $static = false) {
		
		$urls=array();
		$results=array();
		
		foreach($calls as $k=>$call){
			$url = $this->format_url($call);
			//Put cached data in resulsts and urls to call in urls
			if($this->cache !== null && $this->cache->has($url)){
				
				if (self::DECODE_ENABLED) {
					$results[$k] = json_decode($this->cache->get($url), true);
				}else{
					$results[$k] = $this->cache->get($url);
				}
				
			} else {
				$urls[$k] = $url;
			}
		}
		
		$callResult=$this->multiple_threads_request($urls);
		
		foreach($callResult as $k=>$result){
			if($this->cache !== null){
				$this->cache->put($urls[$k], $result, self::CACHE_LIFETIME_MINUTES * 60);
			}
			if (self::DECODE_ENABLED) {
				$results[$k] = json_decode($result, true);
			}else{
				$results[$k] = $result;
			}
		}
		
		return array_merge($results);
	}
	
	//creates a full URL you can query on the API
	private function format_url($call){
		return str_replace('{platform}', $this->PLATFORM, $call);
	}


	public function getLastResponseCode(){
		return $this->responseCode;
	}

	public function debug($message) {
		echo "<pre>";
		print_r($message);
		echo "</pre>";
	}
	
	
	public function setPlatform($platform) {
		$this->PLATFORM = $platform;
	}
	
	private function multiple_threads_request($nodes){
		$mh = curl_multi_init();
		$curl_array = array();
		foreach($nodes as $i => $url)
		{
			$curl_array[$i] = curl_init($url);
			curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, array(
				'X-Riot-Token: '. self::API_KEY
				));
			curl_multi_add_handle($mh, $curl_array[$i]);
		}
		$running = NULL;
		do {
			usleep(10000);
			curl_multi_exec($mh,$running);
		} while($running > 0);
	   
		$res = array();
		foreach($nodes as $i => $url)
		{
			$res[$i] = curl_multi_getcontent($curl_array[$i]);
		}
	   
		foreach($nodes as $i => $url){
			curl_multi_remove_handle($mh, $curl_array[$i]);
		}
		curl_multi_close($mh);       
		return $res;
}
	

}

	
