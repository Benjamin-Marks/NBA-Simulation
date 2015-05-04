/**
 * Created by ben on 4/28/15.
 */

/*
 * Source: http://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array
 * Fisher-Yates Shuffle
 */
function shuffle(array) {
    var currentIndex = array.length, temporaryValue, randomIndex ;

    // While there remain elements to shuffle...
    while (0 !== currentIndex) {

        // Pick a remaining element...
        randomIndex = Math.floor(Math.random() * currentIndex);
        currentIndex -= 1;

        // And swap it with the current element.
        temporaryValue = array[currentIndex];
        array[currentIndex] = array[randomIndex];
        array[randomIndex] = temporaryValue;
    }

    return array;
}

function getProb(prob) {
    if (prob < .5) {
        return .5
    } else if (prob < 2) {
        return .55;
    } else if (prob < 4) {
        return .56;
    } else if (prob < 6) {
        return .7;
    } else if (prob < 8) {
        return .8;
    } else if (prob < 10) {
        return .85;
    } else if (prob < 12) {
        return .95;
    } else {
        return .99;
    }
}

var counter = 0;
var teamRanks = new Array(30); //Hack all the code together TODO: make this more elegant?
var games = new Array(29); //TODO: Same as teamRanks


function finishSim() {
    //Replace each opponent listing with an array that is [team1, team2, team1score, team2score]
    for (var i = 0; i < games.length; i++) {
        for (var j = 0; j < games[i].length; j++) {

            //Determine expected point spread
            //i = team1, games[i][j] = team2
            var favor1 = true;
            var pointDiff = (teamRanks[i] - teamRanks[games[i][j]]);
            if (pointDiff < 0) {
                favor1 = false;
                pointDiff *= -1;
            }
            if (pointDiff > 6) {
                pointDiff -= .95 * (pointDiff - 6);
            }

            //Compress pointDiff TODO
            pointDiff = getProb(pointDiff) - Math.random();
            var win1 = (pointDiff > 0); //Did favorite win?
            if (favor1 == false) {
                win1 = !win1;
            }
            if (pointDiff < 0) {
                pointDiff *= -1;
            }
            pointDiff = Math.floor((pointDiff * 30) + 1); //max spread 30 points, just make it linear
            //Loser points set based on rank with some randomness. TODO make more elegant?
            var doAdd = 1; //Do we add or subtract from rank?
            if (Math.random() > .5) {
                doAdd = -1;
            }

            var loserPoints = 0;
            if (win1) {
                loserPoints += teamRanks[i]
            } else {
                loserPoints += teamRanks[games[i][j]];
            }
            loserPoints = Math.floor(Number(loserPoints) + (doAdd * ((Math.random() * 15) + 1)));
            //Constrain loser/winner points to be between 90-125
            if (loserPoints < 90) { //Adjust for variation
                loserPoints += Math.floor((90 - loserPoints) * .75);
            }
            var winnerPoints = loserPoints + pointDiff;
            if (winnerPoints > 125) {
                winnerPoints -= Math.floor((winnerPoints - 125) * .75);
            }
            var gameData;
            if (win1) {
                gameData = [i, games[i][j], winnerPoints, loserPoints];
            } else {
                gameData = [i, games[i][j], loserPoints, winnerPoints];
            }
            games[i][j] = gameData;
        }
    }
    //Shuffle the list and make our schedule
    var sortGames = new Array(teams.length);
    for (var i = 0; i < sortGames.length; i++) {
        sortGames[i] = new Array(82);
    }

    //Go through the list and do our team's schedule
    for (var i = 0; i < sortGames.length - 1; i++) {
        //Shuffle our games
        games[i] = shuffle(games[i]);
        var curGame = 0;
        var rowLength = games[i].length;
        var addedGame = true;
        //put in our games
        while (addedGame == true) {
            addedGame = false;
            for (var j = 0; j < sortGames[1].length; j++) {
                if (curGame == rowLength) {
                    break;
                }
                if (sortGames[i][j] != null) {
                    continue;
                }
                if (sortGames[games[i][curGame][1]][j] != null) {
                    continue;
                }
                addedGame = true;
                sortGames[i][j] = games[i][curGame];
                sortGames[games[i][curGame][1]][j] = games[i][curGame];
                curGame += 1;
            }
        }
    }

    //Get the Results Div
    var simResults = document.getElementById('simResults');
    simResults.innerHTML = "<h1>Games by Team</h1>";
    for (var i = 0; i < sortGames.length ; i++) {
        //Make header and table element
        var tableDiv = document.createElement("div");
        var header = document.createElement("h2");
        header.innerHTML = teams[i];

        var table = document.createElement("table");
        var htmlString = "";
        //Each Team
        htmlString +="<tr><th>Opponent</th><th>Score</th></tr>";
        for (var j = 0; j < sortGames[i].length; j++) {

            try {
                var weAway = 0;
                if (sortGames[i][j][0] != i) {
                    weAway = 1;
                }
                htmlString += "<tr><td>" + teams[sortGames[i][j][!weAway + 0]];
                htmlString += "</td><td>" + sortGames[i][j][weAway + 2];
                htmlString += "-" + sortGames[i][j][!weAway + 2] + "</td></tr>";
            } catch (e) {
                //Game wasn't created. So put in random game TODO: Maaaaaaybe actually solve this bug
                //console.log("Caught error: ensue hack-togethering");
                var gameData = [teams[i], teams[Math.floor((Math.random() * 29) + 1)], (Math.floor((Math.random() * 40) + 1) + 80),
                    (Math.floor((Math.random() * 40) + 1) + 80)];
                sortGames[i][j] = gameData;
                htmlString += "<tr><td>" + gameData[1];
                htmlString += "</td><td>" + gameData[2];
                htmlString += "-" + gameData[3] + "</td></tr>";
            }
        }
        table.innerHTML = htmlString;
        tableDiv.appendChild(header);
        tableDiv.appendChild(table);
        tableDiv.setAttribute("class", "resTable");
        simResults.appendChild(tableDiv);
    }
    //Now get win-loss record for each team over the regular season
    var teamRecord  = new Array(30);
    for (var i = 0; i < teamRecord.length; i++) {
        var wins = 0;
        for(var j = 0; j < sortGames[i].length; j++) {
            var weAway = 0;
            if (sortGames[i][j][0] != i) {
                weAway = 1;
            }
            if (sortGames[i][j][weAway + 2] > sortGames[i][j][!weAway + 2]){
                wins++;
            }
        }
        teamRecord[i] = [i, wins];
    }
    //Bubble sort #YOLOSWAG
    var swapped;
    do {
        swapped = false;
        for (var i=0; i < teamRecord.length-1; i++) {
            if (teamRecord[i][1] < teamRecord[i+1][1]) {
                var temp = teamRecord[i];
                teamRecord[i] = teamRecord[i+1];
                teamRecord[i+1] = temp;
                swapped = true;
            }
        }
    } while (swapped);

    //And do the output
    var seasonRes = document.getElementById("simSeason");
    seasonRes.innerHTML = "<h1>Regular Season Results</h1>";
    var table = document.createElement("table");
    var htmlString = "";
    htmlString += "<tr><td>Team</td>";
    for (var i = 0; i < teamRecord.length; i++) {
        htmlString+= "<td>" + teams[teamRecord[i][0]] + "</td>";
    }
    htmlString += "</tr><tr><td>Record</td>";
    for (var i = 0; i < teamRecord.length; i++) {
        htmlString+= "<td>" + teamRecord[i][1] + "-" + (82 - teamRecord[i][1])  + "</td>";
    }
    htmlString += "</tr>";
    table.innerHTML = htmlString;
    seasonRes.appendChild(table);
    //Hide the player swapping div
    document.getElementById("simSetup").setAttribute("style", "display: none");
    document.getElementById("simtitle").setAttribute("style", "display: none");
}


//Teams are hard-coded in, TODO: Fix this at some point
//Divisions are every 5 teams, conference is first/last 15
var teams = ["TOR", "BOS", "BRK", "PHI", "NYK",
    "CLE", "CHI", "MIL", "IND", "DET",
    "ATL", "WAS", "MIA", "CHO", "ORL",
    "POR", "OKC", "UTA", "DEN", "MIN",
    "GSW", "LAC", "PHO", "SAC", "LAL",
    "HOU", "MEM", "SAS", "DAL", "NOP"];

function simulate() {
    console.log("entering Sim");
    /*
     Each team have to play:
     4 games against the other 4 division opponents, [4x4=16 games]
     4 games against 6 (out-of-division) conference opponents, [4x6=24 games]
     3 games against the remaining 4 conference teams, [3x4=12 games]
     2 games against teams in the opposing conference. [2x15=30 games]
     */
    //Make the schedule

    //5 year rotation schedule
    //We just put in one year and use it TODO: implement 5 year rotation ->Is this also hardcoded values?
    var rotate1 = new Array(20);
    //Integer values refer to index in above teams array
    rotate1[0] = [5, 6, 9, 10, 13, 14];
    rotate1[1] = [5, 6, 8, 12, 13, 14];
    rotate1[2] = [5, 6, 7, 10, 11, 12];
    rotate1[3] = [7, 8, 9, 10, 11, 12];
    rotate1[4] = [7, 8, 9, 11, 13, 14];
    rotate1[5] = [10, 11, 12];
    rotate1[6] = [11, 13, 14];
    rotate1[7] = [10, 12, 14];
    rotate1[8] = [11, 12, 13];
    rotate1[9] = [10, 13, 14];
    rotate1[10] = [20, 22, 24, 26, 27, 28];
    rotate1[11] = [20, 22, 23, 26, 27, 29];
    rotate1[12] = [20, 21, 23, 25, 26, 28];
    rotate1[13] = [21, 23, 24, 25, 27, 29];
    rotate1[14] = [21, 22, 24, 25, 28, 29];
    rotate1[15] = [25, 28, 29];
    rotate1[16] = [25, 26, 27];
    rotate1[17] = [25, 27, 28];
    rotate1[18] = [26, 27, 29];
    rotate1[19] = [26, 28, 29];


    var conf4Play = new Array(teams.length); //Keeps track of num of times this team has done 4 games with non-div opps
    for (var i = 0; i < conf4Play.length; i ++) { //Init to 0
        conf4Play[i] = 0;
    }

    for (var i = 0; i < 29; i++) { //Last team will have all their games determined
        var rem4Conf = 6 - conf4Play[i];
        var curDiv = Math.floor(i / 5);
        var curConf = Math.floor(i / 15);
        var curGame = 0;
        var remGames = 82 - (4 * (i % 5) + 30 * curConf + (6 - rem4Conf)); //Subtract division games accounted for, non-conf games, and excess 4 gamers
        if (i % 15 >= 10) {  //Have used all non-div conference opponents
            remGames -= 3 * 10;
        } else if (i % 15 >= 5) { //Have used 5 non-div conference opponents
            remGames -= 3 * 5;
        }

        games[i] = new Array(remGames);

        //Division Opponent Games
        for (var j = i % 5; j < 5; j++) {
            if (teams[curDiv * 5 + j] == teams[i]) {
                continue;
            }
            for (var k = 0; k < 4; k++) {
                games[i][curGame] = curDiv * 5 + j;
                curGame++;
            }
        }
        //Get other Conference members
        if (curDiv % 3 != 2) {

            var curSpot = 0;
            var numConfOpps = 10 - 5 * (curDiv % 3);
            var confOpp = new Array(numConfOpps);
            for (j = i % 15; j < 15; j++) {
                //Skip if it's our division
                if (j < 5 && curDiv % 3 == 0) {
                    j = 5;
                }
                if (j >= 5 && j < 10 && curDiv % 3 == 1) {
                    j = 10;
                }
                confOpp[curSpot] = j + curConf * 15;
                curSpot++;
            }
            //Add games for conference players
            for (j = 0; j < confOpp.length; j++) {
                for (k = 0; k < 3; k++) {
                    games[i][curGame] = confOpp[j];
                    curGame++;
                }
                if (rem4Conf > 0 && (i % 15 < 10)) {
                    //If team matches rotate1, do 4 games
                    for (var l = 0; l < rotate1[i % 15 + 10 * curConf].length; l++) {
                        if (rotate1[i % 15 + 10 * curConf][l] == confOpp[j]) {
                            games[i][curGame] = confOpp[j];

                            curGame++;
                            conf4Play[confOpp[j]] += 1;
                            rem4Conf--;
                            break;
                        }
                    }
                }
            }

            //Hopefully this never happens?
            if (rem4Conf != 0) {
                console.log("ERROR: " + teams[i] + " unable to get all 4conf games, has " + rem4Conf + " left");
            }
        }

        //Get out of conference opponents
        if (curConf == 0) {
            for (j = 15; j < 30; j++) {
                games[i][curGame] = j;
                curGame++;
                games[i][curGame] = j;
                curGame++;
            }
        }
        //Check this team's schedule length
        if (curGame != remGames) {
            console.log("ERROR: We got " + curGame + " games, should have " + remGames);
            console.log("Team: " + teams[i]);
            console.log(games[i]);
        }
    }
    console.log("Finished schedule");
    //Determine the relative ranks for each team's roster
    for (var i = 0; i < teamRanks.length; i++) {
        //Pass our players to PHP, returns an integer for rank
        var theurl = 'getRank.php?';
        var teamRow = document.getElementById(teams[i]);
        var playerIDs = new Array(30);
        //Now iterate through the team to grab the players
        var teamPieces = teamRow.getElementsByClassName('playerID');
        for (var j = 0; j < 30; j++) {
            playerIDs[j] = teamPieces.item(j).innerHTML;
        }

        theurl += 'Player1=' + playerIDs[0];
        for (var j = 2; j <= 30; j++) {
            theurl += '&Player' + j + '=' + playerIDs[j - 1];
        }
        (function(_i) {
            $.ajax({
                url: theurl,
                success: function (data) {
                    counter++;
                    teamRanks[_i] = data;
                    if (counter == 30) {
                        finishSim();
                    }
                }
            });
        })(i);
    }
}


function updateTeam(team) {
    var theurl = 'updateTeam.php?Team=' + team;
    var teamRow = document.getElementById(team);
    var playerIDs = new Array(30);
    //Now iterate through the team to grab the players
    var teamPieces = teamRow.getElementsByClassName('playerID');
    for (var i = 0; i < 30; i++) {
        playerIDs[i] = teamPieces.item(i).innerHTML;
    }

    theurl += '&Player1=' + playerIDs[0];
    for (var i = 2; i <= 30; i++) {
        theurl += '&Player' + i + '=' + playerIDs[i - 1];
    }
    $.ajax({
        url: theurl,
        success: function(data) {
            $(".visiblePlayers").html(data);
        }
    });
}

function updatePlayer(id, newTeam) {
    //Find spot in new team - if space, add it, if not, alert we can't
    var newRow = document.getElementById(newTeam);
    var newMates = newRow.getElementsByClassName('playerID');
    var foundSpace = false;
    for (var i = 0; i < 30; i++) {
        if (newMates.item(i).innerHTML == -1) {
            foundSpace = true;
            newMates.item(i).innerHTML = id;
            break;
        }
    }
    if (foundSpace == false){
        alert("ERROR: Cannot have more than 30 players on a team. This change was not processed");
        return;
    }

    //Find current spot in the teams array and delete it
    var oldTeam = document.getElementById('TheTeam').value;
    var oldMates = document.getElementById(oldTeam).getElementsByClassName('playerID');
    var foundOld = false;
    for (var i = 0; i < 30; i++) {
        if (oldMates.item(i).innerHTML == id) {
            oldMates.item(i).innerHTML = -1;
            return;
        }
    }
    var numMin = 5 * 48;

    if (foundOld == false) {
        alert("Could not find player's old reference. This should never happen");
    }
}