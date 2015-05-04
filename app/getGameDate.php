<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/26/15
 * Time: 3:43 PM
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

$Team1 = pg_escape_string($_GET["Team1"]);
$Team2 = pg_escape_string($_GET["Team2"]);

$baseQuery = "hello";
if ($Team1 == -1) {
    $baseQuery = "SELECT DISTINCT gdate FROM games WHERE hometeam='" . $Team2 . "' OR awayteam='" . $Team2 . "' ORDER BY gdate DESC";
} else if ($Team2 == -1) {
    $baseQuery = "SELECT DISTINCT gdate FROM games WHERE hometeam='" . $Team1 . "' OR awayteam='" . $Team1 . "' ORDER BY gdate DESC";
} else {
    $baseQuery = "SELECT DISTINCT gdate FROM games WHERE (hometeam='" . $Team1 . "' AND awayteam='" . $Team2 . "') OR (hometeam='" . $Team2 . "' AND awayteam='" . $Team1 . "') ORDER BY gdate DESC";
}

$result = pg_query($baseQuery);

echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Date</option>";
while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
    echo "<option value='" . $line[0]."'>" . $line[0] . "</option>";
}
