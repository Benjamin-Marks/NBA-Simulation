<?php
/**
 * User: ben
 * Date: 4/25/15
 * Time: 6:07 PM
 */
require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());


$teamQuery = 'SELECT DISTINCT homeTeam FROM games';
$playerQuery = 'SELECT * FROM players WHERE playerID = 2';



$query = $teamQuery;
$result = pg_query($query) or die ('Query failed: ' . pg_last_error());

?>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>G9 Database Systems Project</title>
    <link href="main.css" rel="stylesheet" type="text/css"/>
    <script>
        function pressSearch() {
            if (document.getElementById("criteria").getAttribute("display") == "0") {
                document.getElementById("criteria").setAttribute("display", "1");
                document.getElementById("criteria").setAttribute("style", "display: block;");
                document.getElementById("searchbar").setAttribute("style", "border-bottom: 1px solid #000000;");
                document.getElementById("arrow").setAttribute("style", "transform: rotate(0deg);");
            } else {
                document.getElementById("criteria").setAttribute("display", "0");
                document.getElementById("criteria").setAttribute("style", "display: none;");
                document.getElementById("searchbar").setAttribute("style", "border-bottom: 0px;");
                document.getElementById("arrow").setAttribute("style", "transform: rotate(-90deg);");
            }
        }
    </script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

    <script type="text/javascript">
        function getPlayer(team) {
            $.ajax({
                url: 'getPlayersOnTeam.php?PlayerTeam=' + team,
                success: function(data) {
                    $(".PlayerName").html(data);
                }
            });
        }
        function getGame() {
            $.ajax({
                url: 'getGameDate.php?Team1=' + document.getElementById("GameTeam1").value + '&Team2=' + document.getElementById("GameTeam2").value,
                success: function(data) {
                    $(".GameDate").html(data);
                }
            });
        }
        function searchTeam() {
            if (document.getElementById("TheTeam").value == -1) {
                alert("Please Select a Team");
            } else {
                $.ajax({
                    url: 'searchTeam.php?Team=' + document.getElementById("TheTeam").value,
                    success: function (data) {
                        $(".searchResult").html(data);
                    }
                });
            }
        }
        function searchPlayer() {
            if (document.getElementById("PlayerName").value == -1) {
                alert("Please Select a Player");
            } else {
                $.ajax({
                    url: 'searchPlayer.php?Player=' + document.getElementById("PlayerName").value,
                    success: function (data) {
                        $(".searchResult").html(data);
                    }
                });
            }
        }
        function searchGame() {
            if (document.getElementById("GameTeam1").value == -1 || document.getElementById("GameTeam2").value == -1 || document.getElementById("GameDate").value == -1) {
                alert("Please Select the Game's Teams and Date");
            } else {
                $.ajax({
                    url: 'searchGame.php?Team1=' + document.getElementById("GameTeam1").value + '&Team2=' + document.getElementById("GameTeam2").value + '&Date=' +
                    document.getElementById("GameDate").value,
                    success: function (data) {
                        $(".searchResult").html(data);
                    }
                });
            }
        }
    </script>
</head>
<body>
<div id="wrapper" class="nopad">
    <a id ="homeLink" href="."><p>RETURN TO MAIN MENU</p></a>
    <ul id="searchbar" class="search" onclick="pressSearch()">
        <li unselectable="on" class="noSelect">
            <img id ="arrow" src="images/arrow.png" />
        </li>
        <li unselectable="on" class="noSelect">
            <p unselectable="on" class="noSelect">Search Criteria</p>
        </li>
        <li unselectable="on" class="noSelect">
            <img id="logo" src="images/logo.jpg" />
        </li>
    </ul>
    <ul id="criteria" class="search">
        <li id="teamSearch">
            <h2>Team</h2>
            <?php
            echo "<select name=\"TheTeam\" id=\"TheTeam\">";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Team Name</option>";
            $teamQuery = 'SELECT DISTINCT homeTeam FROM games ORDER BY homeTeam';
            $result = pg_query($teamQuery) or die ('Query failed: ' . pg_last_error());
            while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
                echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
            }
            echo "</select>";
            ?>
            <button id="teamButton" type="button" onclick="searchTeam()">Search!</button>
        </li>
        <li id="playerSearch" class="mid">
            <h2>Player</h2>
            <?php
            echo "<select name=\"PlayerTeam\" class=\"PlayerTeam\" onchange=\"getPlayer(this.value)\">";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Team Name</option>";
            $teamQuery = 'SELECT DISTINCT homeTeam FROM games ORDER BY homeTeam';
            $result = pg_query($teamQuery) or die ('Query failed: ' . pg_last_error());
            while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
                echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
            }
            echo "</select>";
            echo "<select name=\"PlayerName\" id=\"PlayerName\" class=\"PlayerName\">";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Player Name</option>";
            $playerQuery = 'SELECT name FROM players';
            $result = pg_query($playerQuery) or die ('Query failed: ' . pg_last_error());
            while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
                echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
            }
            echo "</select>";
            ?>
            <button id="playerButton" type="button" onclick="searchPlayer()">Search!</button>
        </li>
        <li id="gameSearch">
            <h2>Game</h2>
            <?php
            echo "<select name=\"GameTeam1\" id=\"GameTeam1\" onchange=\"getGame()\">";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Team Name</option>";
            $teamQuery = 'SELECT DISTINCT homeTeam FROM games ORDER BY homeTeam';
            $result = pg_query($teamQuery) or die ('Query failed: ' . pg_last_error());
            while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
                echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
            }
            echo "</select>";
            echo "<select name=\"GameTeam2\" id=\"GameTeam2\" onchange=\"getGame()\">";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Team Name</option>";
            $teamQuery = 'SELECT DISTINCT homeTeam FROM games ORDER BY homeTeam';
            $result = pg_query($teamQuery) or die ('Query failed: ' . pg_last_error());
            while($line = pg_fetch_array($result, null, PGSQL_NUM)) {
                echo "<option value='" . $line[0]."'>" . $line[0]."</option>";
            }
            echo "</select>";
            echo "<select name=\"GameDate\" id=\"GameDate\" class=\"GameDate\">";
            echo "<option value=\"-1\" selected=\"selected\" disabled=\"disabled\">Date</option>";
            echo "</select>";
            ?>
            <button id="gameButton" type="button" onclick="searchGame()">Search!</button>
        </li>
    </ul>
    <div class="searchResult">

    </div>
</div>
</body>
</html>

<?php
pg_free_result($result);
pg_close($dbconn);
?>
