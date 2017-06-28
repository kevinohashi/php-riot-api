<?php
include('php-riot-api.php');
include('FileSystemCache.php');

//testing classes
//using double quotes seems to make all names work (see issue: https://github.com/kevinohashi/php-riot-api/issues/33)
$api = new riotapi('euw1', new FileSystemCache('cache/'));

$id=23516141;

// $r = $api->getChampion();
// $r = $api->getChampion(true);
// $r = $api->getChampionMastery(23516141);
// $r = $api->getChampionMastery(23516141,1);
// $r = $api->getCurrentGame(23516141);
// $api->setPlatform("na1");
// $r = $api->getStatic("champions", 1, "locale=fr_FR&tags=image&tags=spells");
// $api->setPlatform("euw1");
// $r = $api->getMatch(2898677684);
// $r = $api->getMatch(2898677684,false);
// $r = $api->getTimeline(2898677684);
// $r = $api->getMatchList(27695644);
// $params = array(
	// "queue"=>array(4,8),
	// "beginTime"=>1439294958000
// );
// $r = $api->getMatchList(27695644, $params);
// $r = $api->getRecentMatchList(27695644);
// $r = $api->getLeague(24120767);
// $r = $api->getLeaguePosition(24120767);
// $r = $api->getChallenger();
// $r = $api->getMaster();


try {
    $r = $test->getSummonerByName($summoner_name);
    print_r($r);
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
};
echo "<br>\r\n testing cache:";
try {
    $r = $testCache->getSummoner($summoner_id);
    print_r($r);
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
};

?>