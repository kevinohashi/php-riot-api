php-riot-api
============

PHP Wrapper for Riot Games API allows you to quickly make calls to the RIOT API with a proper API Key. Key features include
caching (if enabled) and rate limiting.
Simply replace API_KEY_HERE with your API key from [Riot Games](http://developer.riotgames.com/sign-in?fhs=true).


Testing.php is a simple testing class that shows how to call all the functions.

Getting Started
------------

 - Replace API_KEY_HERE
 - Create folder called 'cache' wherever the script is (make sure it's writeable by php-riot-api)
 - Create an instance of riotapi - $instance = new riotapi($region); 
 - $region can be na, euw, eune, br, tr (br/tr only can call getLeague() and getTeam() functions)
 - Make Calls to the functions listed below and receive JSON data
 - CACHE_ENABLED is true by default, if you don't want to use caching or are running into issues, set it to false
 - DECODE_ENABLED is true by default. If you want your returns to be pure JSON and not an associative array, set it to false 

Functions
------------

	//Returns all champion information.
	getChampion();

	//performs a static call. Not counted in rate limit.
	getStatic($call, $id);

	//New Riot API call. Returns match details given a match id.
	getMatch($matchId);

	//Returns a user's matchHistory given their summoner id.
	getMatchHistory($summoner_id);

	//Returns game statistics given a summoner's id.
	getGame($summoner_id);

	//Returns the league of a given summoner.
	getLeague($summoner_id);
	getLeague($summoner_id, "entry");

	//Returns league information given a *list* of teams.
	getLeagueByTeam($team_ids);

	//Returns the challenger ladder.
	getChallenger();

	//Returns a summoner's stats given summoner id.
	getStats($summoner_id);
	getStats($summoner_id,'ranked');

	//returns a summoner's id
	getSummonerId($summoner_name);

	//Returns summoner info given summoner id.
	getSummoner($summoner_id);
	getSummoner($summoner_id,'masteries');
	getSummoner($summoner_id,'runes');
	getSummoner($summoner_id,'name');

	//Gets a summoner's info given their name, instead of id.
	getSummonerByName($summoner_name);

	//Gets the teams of a summoner, given summoner id.
	getTeam($summoner_id);

Not Complete
------------

Region Checking - Some functions are only available in certain regions and not in others.

Name Sanitization - Not sure how to handle all types of names

Error Code Handling - This assumes the request works every time
