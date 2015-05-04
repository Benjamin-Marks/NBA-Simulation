<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/26/15
 * Time: 1:30 PM
 */

require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

/*
 * Go Into performance
 * select playerid, where team is what we want
 * then, get all the games they played from performance
 * if the most recent date on a gameid is still this team, keep the playerid
 * then convert playerids to names
 */

$allplayersquery = "(SELECT DISTINCT playerid FROM performance WHERE teamname = '" . pg_escape_string($_GET["PlayerTeam"]) . "')";
$allgamesquery = "(SELECT playerid, gameid, teamname FROM performance WHERE playerid IN " . $allplayersquery . ")";
$withdates = "(SELECT playerid, teamname, gdate FROM games JOIN " . $allgamesquery . " AS x ON games.gameid=x.gameid)";
$maxdate = "(SELECT playerid, max(gdate) AS maxdate FROM " . $withdates . " AS y GROUP BY playerid)";
$realmax = "(SELECT maxdates.playerid, teamname FROM " . $maxdate . " AS maxdates JOIN  " . $withdates .
    " AS alldates  ON maxdates.playerid = alldates.playerid AND maxdates.maxdate = alldates.gdate)";
$realteams = "(SELECT playerid FROM " . $realmax . " AS bae WHERE teamname = '" . pg_escape_string($_GET["PlayerTeam"]) . "')";


$query = "SELECT name FROM players WHERE playerid IN " . $realteams;

$result = pg_query($query);

echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Player Name</option>";
while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
    echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
}
