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
	const API_URL_1_1 = 'http://prod.api.pvp.net/api/lol/{region}/v1.1/';
	const API_URL_1_2 = 'http://prod.api.pvp.net/api/lol/{region}/v1.2/';
	const API_URL_1_3 = 'http://prod.api.pvp.net/api/lol/{region}/v1.3/';
	const API_URL_1_4 = 'http://prod.api.pvp.net/api/lol/{region}/v1.4/';
	const API_URL_2_1 = 'http://prod.api.pvp.net/api/lol/{region}/v2.1/';
	const API_URL_2_2 = 'http://prod.api.pvp.net/api/lol/{region}/v2.2/';
	const API_URL_2_3 = "http://prod.api.pvp.net/api/lol/{region}/v2.3/";
	const API_URL_STATIC_1_2 = 'http://prod.api.pvp.net/api/lol/static-data/{region}/v1.2/';

	const API_KEY = 'INSERT_API_KEY_HERE';

	// Rate limit for 10 minutes
	const LONG_LIMIT_INTERVAL = 600;
	const RATE_LIMIT_LONG = 500;

	// Rate limit for 10 seconds'
	const SHORT_LIMIT_INTERVAL = 10;
	const RATE_LIMIT_SHORT = 10;

	// Cache variables
	const CACHE_LIFETIME_MINUTES = 60;
	const CACHE_ENABLED = true;
	private $REGION;

	public function __construct($region)
	{
		$this->REGION = $region;

		$this->shortLimitQueue = new SplQueue();
		$this->longLimitQueue = new SplQueue();


	}

	public function getChampion(){
		$call = 'champion';

		//add API URL to the call
		$call = self::API_URL_1_2 . $call;

		return $this->request($call);
	}

	//performs a static call. Not counted in rate limit.
	public function getStatic($call=null, $id=null) {
		$call = self::API_URL_STATIC_1_2 . $call . "/" . $id;
		
		return $this->request($call);
	}

	public function getGame($id){
		$call = 'game/by-summoner/' . $id . '/recent';

		//add API URL to the call
		$call = self::API_URL_1_3 . $call;

		return $this->request($call);
	}

	public function getLeague($id, $entry=null){
		$call = 'league/by-summoner/' . $id . "/" . $entry;

		//add API URL to the call
		$call = self::API_URL_2_3 . $call;

		return $this->request($call);
	}
	public function getChallenger() {
		$call = 'league/challenger?type=RANKED_SOLO_5x5';

		//add API URL to the call
		$call = self::API_URL_2_3 . $call;
		return $this->request($call, true);
	}

	public function getStats($id,$option='summary'){
		$call = 'stats/by-summoner/' . $id . '/' . $option;

		//add API URL to the call
		$call = self::API_URL_1_3 . $call;

		return $this->request($call);
	}
	//returns a summoner's id
	public function getSummonerId($name) {
			$summoner = $this->getSummonerByName($name);
			return $summoner[$name]["id"];
	}		

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


	public function getSummonerByName($name){


		//use rawurlencode for special characters
		$call = 'summoner/by-name/' . rawurlencode($name);

		//add API URL to the call
		$call = self::API_URL_1_4 . $call;

		return $this->request($call);
	}


	public function getTeam($id){
		$call = 'team/by-summoner/' . $id;

		//add API URL to the call
		$call = self::API_URL_2_2 . $call;

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

	private function request($call, $otherQueries=false) {

		//probably should put rate limiting stuff here
		// Check rate-limiting queues
		$this->updateLimitQueue($this->longLimitQueue, self::LONG_LIMIT_INTERVAL, self::RATE_LIMIT_LONG);
		$this->updateLimitQueue($this->shortLimitQueue, self::SHORT_LIMIT_INTERVAL, self::RATE_LIMIT_SHORT);

		//format the full URL
		$url = $this->format_url($call, $otherQueries);

		//caching
		if(self::CACHE_ENABLED){
			$cacheFile = 'cache/' . md5($url);

		    if (file_exists($cacheFile)) {
		        $fh = fopen($cacheFile, 'r');
		        $cacheTime = trim(fgets($fh));

		        // if data was cached recently, return cached data
		        if ($cacheTime > strtotime('-'. self::CACHE_LIFETIME_MINUTES . ' minutes')) {
		            $data = fread($fh,filesize($cacheFile));
		            return $data;
		        }

		        // else delete cache file
		        fclose($fh);
		        unlink($cacheFile);
		    }
		}

		//call the API and return the result
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);

		if(self::CACHE_ENABLED){
			//create cache file
			file_put_contents($cacheFile, time() . "\n" . $result);
		}	
		return $result;
	}

	//creates a full URL you can query on the API
	private function format_url($call, $otherQueries=false){
		//because sometimes your url looks like .../something/foo?query=blahblah&api_key=dfsdfaefe
		if ($otherQueries) {
			return str_replace('{region}', $this->REGION, $call) . '&api_key=' . self::API_KEY;
		}
		return str_replace('{region}', $this->REGION, $call) . '?api_key=' . self::API_KEY;
	}

	public function debug($message) {
		echo "<pre>";
		print_r($message);
		echo "</pre>";
	}
}
