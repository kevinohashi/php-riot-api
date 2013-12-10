<?php
include('php-riot-api.php')
//testing classes
$summoner_name = 'RiotSchmick';
$summoner_id = 585897;

$test = new riotapi();
//$r = $test->getSummonerByName('na',$summoner_name);
//$r = $test->getSummoner('na',$summoner_id);
//$r = $test->getSummoner('na',$summoner_id,'masteries');
//$r = $test->getSummoner('na',$summoner_id,'runes');
//$r = $test->getSummoner('na',$summoner_id,'name');
//$r = $test->getStats('na',$summoner_id);
//$r = $test->getStats('na',$summoner_id,'ranked');
//$r = $test->getTeam('na',$summoner_id);
//$r = $test->getLeague('na',$summoner_id);
//$r = $test->getGame('na',$summoner_id);
//$r = $test->getChampion('na');


print_r($r);

?>