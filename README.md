php-riot-api
============

PHP Wrapper for Riot Games API allows you to quickly make calls to the RIOT API with a proper API Key. Key features include
caching (if enabled) and rate limiting.
Simply replace API_KEY_HERE with your API key from [Riot Games](http://developer.riotgames.com/sign-in?fhs=true).


Testing.php is a simple testing class that shows how to call all the functions.

Getting Started
------------

 - Replace INSERT_API_KEY_HERE
 - Create folder called 'cache' wherever the script is (make sure it's writeable by php-riot-api)
 - Create an instance of riotapi - $instance = new riotapi($platform); 
 - $platform can be na1, euw1, eune1, br1, ru, kr, oc1, la1, la2, jp1, pbe1, tr1 (br/tr only can call getLeague() and getTeam() functions)
 - Make Calls to the functions listed below and receive JSON data
 - Caching is done locally, instantiate php-riot-api with "new riotapi('na1', new FileSystemCache('cache/'));" to create a cache in the subfolder 'cache'
 - DECODE_ENABLED is true by default. If you want your returns to be pure JSON and not an associative array, set it to false 
 - Take a look at testing.php for example code, including error handling, caching

Functions
------------

	//Returns all champion information.
	getChampion();

	//Change platform
	setRegion($region);

	// Returns all free champions.
	getChampion(true);
	getFreeChampions();

	//performs a static call. Not counted in rate limit.
	getStatic($call, $id = null, $params = null);

	//Returns match details including timeline (if exists) given a match id.
	//Use with care, rate limiting is not ready for this function
	getMatch($matchId);
	//Returns match details given a match id, without timeline.
	getMatch($matchId, false);

	//Returns timeline of a match
	getTimeline($matchId)

	//Returns a user's matchList given their account id.
	public function getMatchList($accountId,$params=null)

	//Returns the league of a given summoner.
	getLeague($summoner_id);
	getLeaguePosition($summoner_id);

	//Returns the challenger ladder.
	getChallenger($queue = "RANKED_SOLO_5x5");
	//Returns the master ladder.
	getMaster($queue = "RANKED_SOLO_5x5");

	//returns a summoner's id
	getSummonerId($summoner_name);
	//returns an account's id
	getSummonerAccountId($summoner_name);

	//Returns summoner info given summoner id.
	getSummoner($summoner_id);
	//Returns summoner masteries given summoner id.
	getMasteries($summoner_id);
	//Returns summoner runes given summoner id.
	getRunes($summoner_id);

	//Returns summoner info given account id.
	getSummoner($accountId);

	//Gets a summoner's info given their name, instead of id.
	getSummonerByName($summoner_name);
	
	//Return details of an array of matches
	//Use with care, rate limiting is not ready for this function
	getMatches($ids, $includeTimeline = true)

Not Complete
------------

Region Checking - Some functions are only available in certain regions and not in others.
