<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/26/15
 * Time: 5:17 PM
 */

//get Player ID
//Display all their performances
//Display their current team
//Display their average stats and rank value
require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

$IDSubQuery = "(SELECT playerid FROM players WHERE name='" .pg_escape_string($_GET["Player"]) . "')";

$playerGames = "SELECT teamname, performance.gameid FROM performance JOIN (SELECT gameid, gdate FROM games) AS games ON performance.gameid = games.gameid " .
              "WHERE playerid=" . $IDSubQuery . " ORDER BY gdate DESC";

$teamText = "SELECT teamname FROM games JOIN (" . $playerGames . ") AS list ON games.gameid = list.gameid ORDER BY games.gdate DESC LIMIT 1";

$teamQuery = pg_query($teamText);
$teamLine = pg_fetch_array($teamQuery, null, PGSQL_NUM);
echo "<h2>" . pg_escape_string($_GET["Player"]) . "</h2>";
echo "<h3>Plays For: " . $teamLine[0] . "</h3>";
?>
    <h4>Career Stats: (Values are career averages)</h4>
    <table>
        <tr>
            <th>Expected +/-</th>
            <th>Start %</th>
            <th>Minutes Played</th>
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
        $averages = "SELECT ROUND(AVG(st), 2), date_trunc('second', AVG(mins)) AS played, ROUND(AVG(fgm),2), ROUND(AVG(fga),2), ROUND(AVG(tpm),2),
ROUND(AVG(tpa),2), ROUND(AVG(ftm),2), ROUND(AVG(fta),2), ROUND(AVG(oreb),2), ROUND(AVG(dreb),2),
ROUND(AVG(ast),2), ROUND(AVG(stl),2), ROUND(AVG(blk),2), ROUND(AVG(tov),2), ROUND(AVG(pf),2), rank
FROM performance JOIN players ON players.playerid=performance.playerid WHERE players.playerid=" . $IDSubQuery . "GROUP BY rank";
        $result = pg_query($averages);
        $line = pg_fetch_array($result, null, PGSQL_NUM);
        echo "<tr>";
        //Put expected +/-
        echo "<td>" . $line[15] . "</td>";
        for ($i = 0; $i < 15; $i++) {
            echo "<td>" . $line[$i] . "</td>";
        }
        echo "</tr>";
        ?>
    </table>

    <h4>Per-Game Stats</h4>
    <table>
        <tr>
            <th>Date</th>
            <th>Opponent</th>
            <th>Started</th>
            <th>Minutes Played</th>
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
        //Get gameid and performance data
        //convert gameid to date, opponent
        $perGameQuery = "SELECT * FROM performance JOIN (SELECT gameid, gdate FROM games) AS games ON performance.gameid = games.gameid " .
            "WHERE playerid=" . $IDSubQuery . " ORDER BY gdate DESC";
        $perGameData = pg_query($perGameQuery);
        while ($line = pg_fetch_array($perGameData, null, PGSQL_NUM)) {
            $gameInfo = pg_query("SELECT gdate, homeTeam, awayTeam FROM games WHERE gameid=" . $line[1]);
            $gameArray = pg_fetch_array($gameInfo, null, PGSQL_NUM);
            echo "<td>" . $gameArray[0] . "</td>";
            if ($gameArray[1] == $teamLine[0]) {
                //They played at home, we want away team
                echo "<td>" . $gameArray[2] . "</td>";
            } else {
                echo "<td>" . $gameArray[1] . "</td>";
            }
            for ($i = 3; $i < 18; $i++) {
                echo "<td>" . $line[$i] . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
