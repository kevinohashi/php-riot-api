<?php
include('php-riot-api.php');
//testing classes
$summoner_name = 'RiotSchmick';
$summoner_id = 585897;

$test = new riotapi('na');
//$r = $test->getSummonerByName($summoner_name);
//$r = $test->getSummoner($summoner_id);
//$r = $test->getSummoner($summoner_id,'masteries');
//$r = $test->getSummoner($summoner_id,'runes');
//$r = $test->getSummoner($summoner_id,'name');
//$r = $test->getStats($summoner_id);
//$r = $test->getStats($summoner_id,'ranked');
//$r = $test->getTeam($summoner_id);
//$r = $test->getLeague($summoner_id);
//$r = $test->getGame($summoner_id);
$r = $test->getChampion();


print_r($r);

?>