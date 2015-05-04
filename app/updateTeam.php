<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/27/15
 * Time: 1:43 AM
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

$team = pg_escape_string($_GET["Team"]);


?>
<tr>
    <th>Name</th>
    <th>Expected +/-</th>
    <th>Minutes Played</th>
    <th>Choose Team</th>
</tr>
<?php
//Now echo all the stuff we need to echo
//Get average minutes played
$minPlayed = "(SELECT playerid, date_trunc('second', AVG(mins)) as avgmins FROM performance WHERE playerid IN " . $playerString . "GROUP BY playerid)";
$query = "SELECT x.playerid, name, rank, avgmins FROM players JOIN " . $minPlayed . " AS x ON x.playerid=players.playerid  WHERE players.playerid IN "
            . $playerString . "ORDER BY avgmins DESC";

$result = pg_query($query);
while ($line = pg_fetch_array($result, null, PGSQL_NUM)) {
    echo "<tr>";
    echo "<td>" . $line[1] . "</td>";
    echo "<td>" . $line[2] . "</td>";
    echo "<td>" . $line[3] . "</td>";
    //Setup the Select
    $teamsQuery = pg_query("SELECT DISTINCT hometeam FROM games WHERE hometeam NOT IN ('NJN', 'SEA', 'NOH', 'NOK', 'CHA') ORDER BY hometeam");
    echo "<td><select name='" . $line[0] . "' class='teamAdjust' onchange='updatePlayer(this.name, this.value)'>";
    while ($teamLine = pg_fetch_array($teamsQuery, null, PGSQL_NUM)) {
        echo "<option ";
        if ($team == $teamLine[0]) {
            echo "selected=\"selected\"";
        }
        echo "> " . $teamLine[0] . "</option>";
    }
    echo "</select>";
    echo "</tr>";
}
