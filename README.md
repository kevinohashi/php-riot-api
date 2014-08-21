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
getStatic($call, $id);

getMatch($matchId);

getMatchHistory($summoner_id);

getChallenger();

getSummonerByName($summoner_name);

getSummoner($summoner_id);

getSummoner($summoner_id,'masteries');

getSummoner($summoner_id,'runes');

getSummoner($summoner_id,'name');

getSummonerId($summoner_name);

getStats($summoner_id);

getStats($summoner_id,'ranked');

getTeam($summoner_id);

getLeague($summoner_id);

getGame($summoner_id);

getChampion();

Not Complete
------------

Region Checking - Some functions are only available in certain regions and not in others.

Name Sanitization - Not sure how to handle all types of names

Error Code Handling - This assumes the request works every time