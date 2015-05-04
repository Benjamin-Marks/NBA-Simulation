<?php
/**
 * User: ben
 * Date: 4/25/15
 * Time: 6:08 PM
 */
//Button: Start Simulation
//GUI: allow you to change around rosters
//CHECK: Need to remove inactive teams from the list

require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

?>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>G9 Database Systems Project</title>
    <link href="main.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
    <script type="text/javascript" src ="simulation.js"></script>
</head>
<body>
<?php

echo "<table id=\"teamRoster\" style=\"display: none\">";
$teamsQuery = pg_query("SELECT DISTINCT hometeam FROM games WHERE hometeam NOT IN ('NJN', 'SEA', 'NOH', 'NOK', 'CHA')");
//Get current roster for the teams
while ($curTeam = pg_fetch_array($teamsQuery, null, PGSQL_NUM)) {
    $allplayersquery = "(SELECT DISTINCT playerid FROM performance WHERE teamname = '" . $curTeam[0] . "')";
    $allgamesquery = "(SELECT playerid, gameid, teamname FROM performance WHERE playerid IN " . $allplayersquery . ")";
    $withdates = "(SELECT playerid, teamname, gdate FROM games JOIN " . $allgamesquery . " AS x ON games.gameid=x.gameid)";
    $maxdate = "(SELECT playerid, max(gdate) AS maxdate FROM " . $withdates . " AS y GROUP BY playerid HAVING max(gdate) >= '20140801')";
    $realmax = "(SELECT maxdates.playerid, teamname FROM " . $maxdate . " AS maxdates JOIN  " . $withdates .
        " AS alldates  ON maxdates.playerid = alldates.playerid AND maxdates.maxdate = alldates.gdate)";
    $playerlist = "SELECT playerid FROM " . $realmax . " AS bae WHERE teamname = '" . $curTeam[0] . "'";

    $rosterResult = pg_query($playerlist);

    echo "<tr id=\"" . $curTeam[0] . "\">";

    $i = 0;
    while ($curRoster = pg_fetch_array($rosterResult, null, PGSQL_NUM)) {
        echo "<td class=\"playerID\" name=\"spot:" . $i . "\">";
        echo $curRoster[0];
        echo "</td>";
        $i++;
    }
    for (;$i < 30; $i++) {
        echo "<td class = \"playerID\" name=\"spot:" . $i . "\">-1</td>";
    }
    echo "</tr>";
}

echo "</table>";
?>
<div id="wrapper" class="nopad">
    <a id ="homeLink" href="."><p>RETURN TO MAIN MENU</p></a>
    <div class="pageTitleWrapper" id="simtitle">
        <h1 class="pageTitle" onclick="simulate()">Simulate Season</h1>
    </div>
    <div id="simSetup">
        <div id="adjustPlayers">
            <?php

            echo "<select name=\"TheTeam\" id=\"TheTeam\" onchange='updateTeam(this.value)'>";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Team Name</option>";
            $teamsQuery = pg_query("SELECT DISTINCT hometeam FROM games WHERE hometeam NOT IN ('NJN', 'SEA', 'NOH', 'NOK', 'CHA') ORDER BY hometeam");
            while($line = pg_fetch_array($teamsQuery, null, PGSQL_NUM)) {
                echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
            }
            echo "</select>";
            ?>
            <table id="visiblePlayers" class="floater visiblePlayers">
            </table>
        </div>
        <div id="goButtons">

        </div>
    </div>
    <div id="simSeason">
    </div>
    <div id="simResults">
    </div>
</div>
</body>
</html>
