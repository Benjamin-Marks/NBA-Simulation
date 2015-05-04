<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/26/15
 * Time: 5:17 PM
 */

//Take Team Name
//Find all current players from this year
//Display the players along with their average stats and their rank value
require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

echo "<h2>" . pg_escape_string($_GET["Team"]) . "</h2>";
echo "<h4>Current Roster: (Values are career averages)</h4>";
?>
    <table>
        <tr>
            <td>Player</td>
            <td>Expected +/-</td>
            <td>Minutes Played</td>
            <td>FGM</td>
            <td>FGA</td>
            <td>3PM</td>
            <td>3PA</td>
            <td>FTM</td>
            <td>FTA</td>
            <td>Off. Reb</td>
            <td>Def. Reb</td>
            <td>Assists</td>
            <td>Steals</td>
            <td>Blocks</td>
            <td>Turnovers</td>
            <td>Fouls</td>
        </tr>
<?php
//Get Players
$allplayersquery = "(SELECT DISTINCT playerid FROM performance WHERE teamname = '" . pg_escape_string($_GET["Team"]) . "')";
$allgamesquery = "(SELECT playerid, gameid, teamname FROM performance WHERE playerid IN " . $allplayersquery . ")";
$withdates = "(SELECT playerid, teamname, gdate FROM games JOIN " . $allgamesquery . " AS x ON games.gameid=x.gameid)";
$maxdate = "(SELECT playerid, max(gdate) AS maxdate FROM " . $withdates . " AS y GROUP BY playerid HAVING max(gdate) >= '20140801')";
$realmax = "(SELECT maxdates.playerid, teamname FROM " . $maxdate . " AS maxdates JOIN  " . $withdates .
    " AS alldates  ON maxdates.playerid = alldates.playerid AND maxdates.maxdate = alldates.gdate)";
$playerlist = "(SELECT playerid FROM " . $realmax . " AS bae WHERE teamname = '" . pg_escape_string($_GET["Team"]) . "')";

//Now take ID's and compute average stats
$averages = "SELECT players.playerid, players.rank, date_trunc('second', AVG(mins)) AS played, ROUND(AVG(fgm),2), ROUND(AVG(fga),2), ROUND(AVG(tpm),2),
            ROUND(AVG(tpa),2), ROUND(AVG(ftm),2), ROUND(AVG(fta),2), ROUND(AVG(oreb),2), ROUND(AVG(dreb),2),
            ROUND(AVG(ast),2), ROUND(AVG(stl),2), ROUND(AVG(blk),2), ROUND(AVG(tov),2), ROUND(AVG(pf),2)
            FROM performance JOIN players ON players.playerid=performance.playerid WHERE players.playerid in " . $playerlist . " GROUP BY players.playerid ORDER BY rank DESC";
//Now Take ID's and
$result = pg_query($averages);
$juggernaut = "SELECT * FROM " . $realteams . "AS yolo";
echo $realteams;
while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
    echo "<tr>";
    //Put Player Name
    $nameRes = pg_query("SELECT name from players WHERE playerid='" . $line[0] . "'");
    $nameLine = pg_fetch_array($nameRes, null, PGSQL_NUM);
    echo "<td>" . $nameLine[0] . "</td>";
    //Put expected +/-
    echo "<td>" . $line[1] . "</td>";
    for ($i = 2; $i < 16; $i++) {
        echo "<td>" . $line[$i] . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

if (pg_escape_string($_GET["Team"]) == "NJN") {
    echo "<h3>New Jersey Nets are now Brooklyn Nets. For information on Brooklyn's roster, see Team-BRK</h3>";
} else if (pg_escape_string($_GET["Team"]) == "SEA") {
    echo "<h3>Seattle Supersonics are now Oklahoma City Thunder. For information on their roster, see Team-OKC</h3>";
} else if (pg_escape_string($_GET["Team"]) == "NOH" OR pg_escape_string($_GET["Team"]) == "NOK") {
    echo "<h3>New Orleans Hornets are now New Orleans Pelicans. For information on their roster, see Team-NOP</h3>";
} else if (pg_escape_string($_GET["Team"]) == "CHA") {
    echo "<h3>Charlotte Bobcats are now Charlotte Hornets. For information on their roster, see Team-CHO</h3>";
}
