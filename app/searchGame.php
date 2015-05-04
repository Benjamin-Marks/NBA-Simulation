<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/26/15
 * Time: 5:17 PM
 */


//Get GameID
//Determine score and put that on top
//Display table of all players who played in game
require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

$team1 = pg_escape_string($_GET["Team1"]);
$team2 = pg_escape_string($_GET["Team2"]);
$date = pg_escape_string($_GET["Date"]);

$gameidquery = "SELECT gameid, hometeam FROM games WHERE gdate='" . $date . "'AND (hometeam='" . $team1 .
    "' OR hometeam='" . $team2 . "')";

$result = pg_query($gameidquery) or die ('Query failed: ' . pg_last_error());

$gameid = pg_fetch_array($result, null, PGSQL_NUM);

//Order these appropriately for home/away team
if ($gameid[1] == $team2) {
    echo $gameid[1];
    $temp = $team1;
    $team1 = $team2;
    $team2 = $temp;
}

echo "<h2>Game: " . $team1 . " vs. " . $team2 . "</h2>";

//Get performance info from the game
$performance = "SELECT * FROM performance WHERE gameid=" . $gameid[0];
$result = pg_query($performance) or die ('Query failed: ' . pg_last_error());

$homeScore = 0;
$awayScore = 0;
//Aggregate scores
while ($perfLine = pg_fetch_array($result, null, PGSQL_NUM)) {
    if ($perfLine[2] == $team1) {
        $homeScore += 2 * $perfLine[5] + 1 * $perfLine[7] + $perfLine[9];
    } else {
        $awayScore += 2 * $perfLine[5] + 1 * $perfLine[7] + 1 * $perfLine[9];
    }
}
echo "<h3>Score: " . $homeScore . " to " . $awayScore . "</h3>";
?>

<h4> Individual Player Stats</h4>

<table>
    <tr>
        <th>Name</th>
        <th>Team</th>
        <th>Started</th>
        <th>Min Played</th>
        <th>FGM</th>
        <th>FGA</th>
        <th>3PM</th>
        <th>3PA</th>
        <th>FTM</th>
        <th>FTA</th>
        <th>Off. Reb</th>
        <th>Def. Reb</th>
        <th>Assists</th>
        <th>Steals</th>
        <th>Blocks</th>
        <th>Turnovers</th>
        <th>Fouls</th>
    </tr>
    <?php
        //Reload performance query
    $performance = pg_query("SELECT * FROM performance WHERE gameid=" . $gameid[0] . "ORDER BY teamname, playerid");
    while ($perfline = pg_fetch_array($performance, null, PGSQL_NUM)) {
        $name = pg_fetch_array(pg_query("SELECT name FROM players WHERE playerid=" . $perfline[0]), null, PGSQL_NUM);
        echo "<td>" . $name[0] . "</td>";
        for ($i = 2; $i < 18; $i++) {
            echo "<td>" . $perfline[$i] . "</td>";
        }
        echo "</tr>";
    }
    ?>

</table>
