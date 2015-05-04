<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 4/28/15
 * Time: 4:30 AM
 */

require_once('login.php');
$dbconn = pg_connect("host=db dbname=g9_project user=" . $DB_USER . "password=" . $DB_PASS) or die('Could not connect: ' . pg_last_error());

$query = "SELECT playerid, (2 * AVG(fgm) + AVG(tpm) + AVG(ftm))/((AVG(fga) + .45 * AVG(fta) + AVG(tov) - AVG(oreb)) + .001) " .
    " AS rank FROM performance GROUP BY playerid ORDER BY rank DESC";

$prodQuery = "SELECT playerid, (AVG(tpm) * .064 + AVG(fgm) * .032 + AVG(ftm) * .017 + (AVG(fga)-AVG(fgm)) * -.034 + (AVG(fta)-AVG(ftm)) * -.015 + AVG(oreb) * .034 + AVG(dreb) * .034 + AVG(tov) * -.034 + AVG(stl) * .033 + AVG(BLK) * .02) as rank FROM performance GROUP BY playerid ORDER BY rank DESC";
