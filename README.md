php-riot-api
============

PHP Wrapper for Riot Games API allows you to quickly make calls to the RIOT API with a proper API Key.
Simply replace API_KEY_HERE with your API key from [Riot Games](http://developer.riotgames.com/sign-in?fhs=true)


Testing.php is a simple testing class that shows how to call all the functions

Functions
------------

getSummonerByName($region,$summoner_name);

getSummoner($region,$summoner_id);

getSummoner($region,$summoner_id,'masteries');

getSummoner($region,$summoner_id,'runes');

getSummoner($region,$summoner_id,'name');

getStats($region,$summoner_id);

getStats($region,$summoner_id,'ranked');

getTeam($region,$summoner_id);

getLeague($region,$summoner_id);

getGame($region,$summoner_id);

getChampion($region);

Not Complete
------------

Rate Limiting - You can currently send 5 requests per 10 seconds or 50 per 10 minutes.

Region Checking - Some functions are only available in certain regions and not in others.
