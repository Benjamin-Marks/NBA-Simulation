<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/27/15
 * Time: 11:08 PM
 */
require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

//Get variables

$IDS = array_fill(0, 30, 0);
$playerString = "(";
for ($i = 2; $i <= 31; $i++) {
    $IDS[$i - 2] = pg_escape_string($_GET["Player" . ($i - 1)]);
    $playerString .= "'" . $IDS[$i - 2] . "'";
    if ($i < 31) {
        $playerString .= ",";
    }
}
$playerString .= ")";

//Take our ID's, combine ranks and average minutes

$query = "SELECT players.playerid, rank, EXTRACT(EPOCH FROM date_trunc('minute', AVG(mins)))/60 AS avgmins FROM players JOIN performance ON players.playerid" .
    "=performance.playerid WHERE players.playerid IN " . $playerString . "GROUP BY players.playerid, rank ORDER BY rank DESC";

$results = pg_query($query) or die ('Query Failed: ' . pg_last_error());

$secRemaining = 14400;
$rank = 0;

while ($line = pg_fetch_array($results, null, PGSQL_NUM)) {
    //Combine the lines if we still have minutes
    $secRemaining -= $line[2];
    $rank += $line[1];
    if ($secRemaining <= 0) {
        break;
    }
}

//Hack to make small teams work - average shit together if there's remaining time
if ($secRemaining > 0) {
    //If time remaining
    $percentage = (14400 - $secRemaining)/14400;
    $rank *= 1/$percentage;
}

$rank *= .6;
echo $rank;
