#!/usr/bin/php
<?php
error_reporting(0);

$pluginName ="ScoreBoard";
$myPid = getmypid();

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;

$DEBUG=true;

$skipJSsettings = 1;
include_once("/opt/fpp/www/config.php");
include_once("/opt/fpp/www/common.php");
include_once "functions.inc.php";
include_once 'SPORTS_ABBREVIATIONS.inc.php';
include_once 'COLORS.inc.php';

require ("lock.helper.php");

define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', $pluginName.'.lock');



$SPORTS_TICKER_PLUGIN_NAME="SportsTicker";
$SPORTS_TICKER_SPORTS_FILE="SPORTS.inc.php";

$MATRIX_MESSAGE_PLUGIN_NAME = "MatrixMessage";
$fpp_matrixtools_Plugin = "fpp-matrixtools";
$fpp_matrixtools_Plugin_Script = "scripts/matrixtools";
$FPP_MATRIX_PLUGIN_ENABLED=false;

$MAX_SCORE_DIGITS=3;

//$FONT = "advanced_led_board-7.ttf";
$FONT = "calibri.ttf";
$FONT_PATH = $pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/fonts/";

//the only font size that works is 9 or greater with this font
$FONT_SIZE = "9";

$MATRIX_FUNCTIONS_FILE = "MatrixFunctions.inc.php";

$logFile = $settings['logDirectory']."/".$pluginName.".log";


if(($pid = lockHelper::lock()) === FALSE) {
	exit(0);

}

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);
//get the block size of the blocks in use
foreach ($pluginSettings as $key => $value) {

	//	echo "Key: ".$key." " .$value."\n";

	${$key} = urldecode($value);

}
//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;
//echo "Enabled: ".$ENABLED."<br/> \n";


if($ENABLED != "ON" && $ENABLED != "1") {
	logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use & Restart FPPD Daemon");
	lockHelper::unlock();
	exit(0);
}

$messageQueuePluginPath = $pluginDirectory."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));
if($DEBUG) {
	$messageQueueFile = "/home/pi/media/plugindata/FPP.MessageQueue.test";
}
logEntry("Using message queue file: ".$messageQueueFile);

if(file_exists($messageQueuePluginPath."functions.inc.php"))
{
	include $messageQueuePluginPath."functions.inc.php";
	$MESSAGE_QUEUE_PLUGIN_ENABLED=true;

} else {
	logEntry("Message Queue Plugin not installed, some features will be disabled");
}


if(file_exists($pluginDirectory."/".$SPORTS_TICKER_PLUGIN_NAME."/".$SPORTS_TICKER_SPORTS_FILE))
{
	include_once($pluginDirectory."/".$SPORTS_TICKER_PLUGIN_NAME."/".$SPORTS_TICKER_SPORTS_FILE);
} else {
	logEntry("No sports file found to include.. Is the SportsTicker plugin installed?");
	echo "No sports file to be found..Is the SportsTicker plugin installed?";
	lockHelper::unlock();
	exit(0);
}

if(file_exists($pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/".$MATRIX_FUNCTIONS_FILE))
{
	include_once($pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/".$MATRIX_FUNCTIONS_FILE);
	
} else {
	logEntry("Cannot load the MatrixMessage Functions. Is the MatrixMessage Plugin Installed?");
	echo "Cannot load the Matrix Message Functions. Please install the Matrix Message Plugin";
	lockHelper::unlock();
	exit(0);
}


//CHECK for the Fpp-matrix tools plugin
if(file_exists($pluginDirectory."/". $fpp_matrixtools_Plugin."/".$fpp_matrixtools_Plugin_Script)) {
	logEntry($fpp_matrixtools_Plugin." script: ".$fpp_matrixtools_Plugin_Script. " is installed");	
} else {
	logEntry($fpp_matrixtools_Plugin." is not installed cannot continue. Please install");
	lockHelper::unlock();
	exit(0);
}

//MAIN
//getMatrixPosition($HOME_TEAM_MATRIX,"HOME");
//getMatrixPosition($AWAY_TEAM_MATRIX,"AWAY");
//print_r($pluginSettings);


$FONT="calibri.ttf";

//echo "Home team: ".$pluginSettings['HOME_TEAM']."\n";

$ABBR_HOME_TEAM = getTeamAbbreviationName($SPORTS,$HOME_TEAM);
$ABBR_AWAY_TEAM = getTeamAbbreviationName($SPORTS,$AWAY_TEAM);



if($ABBR_AWAY_TEAM == null) {
	$ABBR_AWAY_TEAM = "AWY";
}
if($ABBR_HOME_TEAM == null) {
	$ABBR_HOME_TEAM = "HME";
}

WriteSettingToFile("ABBR_HOME_TEAM",$ABBR_HOME_TEAM,$pluginName);
WriteSettingToFile("ABBR_AWAY_TEAM",$ABBR_AWAY_TEAM,$pluginName);

logEntry("home team: ".$HOME_TEAM." abbr: ".$ABBR_HOME_TEAM);
logEntry("away team: ".$AWAY_TEAM." abbr: ".$ABBR_AWAY_TEAM);

//run the getSports


$cmd = $pluginDirectory."/".$SPORTS_TICKER_PLUGIN_NAME."/"."getSPORTS.php";
exec($cmd,$getSportsOutput);

//give time to write the file and then we are going to read it :)
sleep(1);

//print_r($getSportsOutput);

//FIXME later to get all the items and rotate the score board???
//also update US! so we t get latest messages

logEntry("Using message queu file: ".$messageQueueFile);

$sportsScoresData = getNewPluginMessages($SPORTS_TICKER_PLUGIN_NAME,$pluginName);

//through and update the score if it is a valid score!!
//score format is: 1421171858| NFL++%7C+Baltimore+31+++New+England+35+%28END+OF+4TH%29+%7C+Carolina+17+++Seattle+31+%28END+OF+4TH%29+%7C+Dallas+21+++Green+Bay+26+%28END+OF+4TH%29+%7C+Indianapolis+24+++Denver+1
//3+%28FINAL%29 | SportsTicker | NFL
//$sportsScoresData[0] = "1421171858| NFL++%7C+Baltimore+31+++New+England+35+%28END+OF+4TH%29+%7C+Carolina+17+++Seattle+31+%28END+OF+4TH%29+%7C+Dallas+21+++Green+Bay+26+%28END+OF+4TH%29+%7C+Indianapolis+24+++Denver+13+%28FINAL%29 | SportsTicker | NFL";

//echo $sportsScoresData."\n";
if($DEBUG)
	print_r($sportsScoresData);
//have to split on the configured sports score ticker separator :)
$SPORTS_TICKER_SEPARATOR = urldecode(ReadSettingFromFile("SEPARATOR","SportsTicker"));
$sportsScoreIndex=0;
$SCORES_READ=false;
$NEW_AWAY_SCORE=0;
$NEW_HOME_SCORE=0;

for($sportsMessageIndex =0;$sportsMessageIndex <= count($sportsScoresData)-1;$sportsMessageIndex++) {

	$sportsScoresMessageParts = explode("|",$sportsScoresData[$sportsMessageIndex]);

	$sportsScoresTmp = explode($SPORTS_TICKER_SEPARATOR,urldecode($sportsScoresMessageParts[1]));

//index 0 will be the SPORT :) yay..we can use this later.

//look for both teams in the same line!!!!
	$sportsScoreIndex=0;
	$SCORES_READ=false;

for($sportsScoreIndex = 0;$sportsScoreIndex<=count($sportsScoresTmp)-1;$sportsScoreIndex++) {
	
	

	if ((strpos($sportsScoresTmp[$sportsScoreIndex],$HOME_TEAM) !== false) && (strpos($sportsScoresTmp[$sportsScoreIndex],$AWAY_TEAM) !== false)) {
		if($DEBUG) {
			echo "Both teams in the line : Index: ".$sportsScoreIndex."\n";
		}
		$SCORES_READ=true;
		$scoreIndex = $sportsScoreIndex;
		//allow for looping all the way through beause we want the LAST update from SportsTicker. if we exit too soon we may not have urrent info
		//and it would update wrong.
		//get the scores for the teams!!!
		//$scoreIndex++;
	//	break;
		
	} else {
		//echo "team: ".$HOME_TEAM.  " & ".$AWAY_TEAM." not in same line \n";
	}
}
}

if($DEBUG) {
	echo "home: ".(int)$HOME_SCORE."\n";
	echo "away: ".(int)$AWAY_SCORE."\n";
}
if($SCORES_READ) {
	//echo "we got a valid score line with the teams: Index: ".$sportsScoreIndex."\n";
	logEntry("We got some new scores");
	
	$NEW_HOME_SCORE = getSportScore($sportsScoresTmp[$scoreIndex], $HOME_TEAM);
	//if($NEW_HOME_SCORE == "") {
	//	$NEW_HOME_SCORE="";
	//}
	$NEW_AWAY_SCORE = getSportScore($sportsScoresTmp[$scoreIndex], $AWAY_TEAM);
	//if($NEW_AWAY_SCORE== "") {
	//	$NEW_AWAY_SCORE="";
	//}
	
} else {
	logEntry("We do not have new scores for: ".$HOME_TEAM. " vs ".$AWAY_TEAM);

	
	//if the scores are zero - we could have just initiise the board with sports names
	//just exit
	if((int)$HOME_SCORE == 0 && (int)$AWAY_SCORE == 0) {
		//catch here to inialize?
		
	} else {
		lockHelper::unlock();
		exit(0);
	}
}



//print_r($sportsScoresTmp);

//update the latest score if it has changed

if((int)$HOME_SCORE <=0) {
	$HOME_SCORE="";
}
if((int)$AWAY_SCORE <=0) {
	$AWAY_SCORE="";
}


//echo "old away score: ".(int)$AWAY_SCORE."\n";
//echo "old home score: ".(int)$HOME_SCORE."\n";

$NEW_SCORES=false;

//now compare the scores of the latest data to what we have now!
//the score could be less if the scrape happened during a review session and the score was updated to early higher
if((int)$NEW_AWAY_SCORE <> (int)$AWAY_SCORE) {
	

	$AWAY_SCORE = $NEW_AWAY_SCORE;
	$NEW_SCORES=true;
	
}

if((int)$NEW_HOME_SCORE <> (int)$HOME_SCORE)  {
	$HOME_SCORE = $NEW_HOME_SCORE;
	$NEW_SCORES=true;
	
}


$AWAY_SCORE = str_pad($AWAY_SCORE, $MAX_SCORE_DIGITS, ' ', STR_PAD_LEFT);

$HOME_SCORE = str_pad($HOME_SCORE, $MAX_SCORE_DIGITS, ' ', STR_PAD_LEFT);

if($DEBUG) {
	echo "away score: ".$AWAY_SCORE."\n";
	echo "home score: ".$HOME_SCORE."\n";
}
//temporarly reset the last read of sporst ticker for testing
WriteSettingtoFile("LAST_READ","0",$SPORTS_TICKER_PLUGIN_NAME);

if($NEW_SCORES) {
	logEntry("We got new scores for one or both teams: UPdating scoreboard: HOME (".$HOME_TEAM."): ".$HOME_SCORE." AWAY (".$AWAY_TEAM."): ".$AWAY_SCORE);
	
	//need to nitialize if there are NO scores at all //just put up the names
} elseif((int)$HOME_SCORE ==0 && (int)$AWAY_SCORE ==0) {
	logEntry("initializing just names on scoreboard");
	
} else {
	logEntry(" we do not have new scores but we'll run the matrix code anyway");
	//just exit
	//dont do anything with the matrix
//	lockHelper::unlock();
	//exit(0);
}



//HOME MATRIX COORDINATES
//just use 0,0 for now

//if using the same matrix, then find the position of the home team and away team
if($HOME_TEAM_MATRIX == $AWAY_TEAM_MATRIX && $HOME_TEAM_SCORE_MATRIX == $AWAY_TEAM_SCORE_MATRIX) 
{
	logEntry("Names on same matrix, calculating positions");
	$AWAY_MATRIX_POS=getMatrixPosition($AWAY_TEAM_MATRIX,"AWAY");
	$HOME_MATRIX_POS=getMatrixPosition($HOME_TEAM_MATRIX,"HOME");
	
	
	//the scores have the same position, just on a different matrix.
	$HOME_SCORE_MATRIX_POS = $HOME_MATRIX_POS;
	$AWAY_SCORE_MATRIX_POS = $AWAY_MATRIX_POS;
	
} else {
	logEntry("individual matrixes for home and away");
	$AWAY_MATRIX_POS="0,0";
	$HOME_MATRIX_POS="0,0";
	
	$HOME_SCORE_MATRIX_POS = "0,0";
	$AWAY_SCORE_MATRIX_POS = "0,0";
}
//$AWAY_MATRIX_POS=getMatrixPosition($AWAY_TEAM_MATRIX,"AWAY");
//$HOME_MATRIX_POS=getMatrixPosition($HOME_TEAM_MATRIX,"HOME");





WriteSettingToFile("HOME_MATRIX_POS",$HOME_MATRIX_POS,$pluginName);
WriteSettingToFile("AWAY_MATRIX_POS",$AWAY_MATRIX_POS,$pluginName);
WriteSettingToFile("HOME_SCORE_MATRIX_POS",$HOME_MATRIX_POS,$pluginName);
WriteSettingToFile("AWAY_SCORE_MATRIX_POS",$AWAY_MATRIX_POS,$pluginName);


//$HOME_ENABLE_CMD = $pluginDirectory."/". $fpp_matrixtools_Plugin."/".$fpp_matrixtools_Plugin_Script. " --blockname \"".$HOME_TEAM_MATRIX."\" --enable 1";

//logEntry("Home enable cmd: ".$HOME_ENABLE_CMD);
//exec($HOME_ENABLE_CMD,$cmdEnableOutput);

//disable it while we are updating
//disableMatrixToolOutput($HOME_TEAM_MATRIX);

//clearMatrix($matrix);

clearMatrix($HOME_TEAM_MATRIX);
clearMatrix($AWAY_TEAM_MATRIX);
clearMatrix($HOME_TEAM_SCORE_MATRIX);
clearMatrix($AWAY_TEAM_SCORE_MATRIX);

$MAX_ABBR_DIGITS=3;
$ABBR_HOME_TEAM = str_pad($ABBR_HOME_TEAM, $MAX_ABBR_DIGITS, ' ', STR_PAD_LEFT);
$ABBR_AWAY_TEAM = str_pad($ABBR_AWAY_TEAM, $MAX_ABBR_DIGITS, ' ', STR_PAD_LEFT);



//updateScoreOnMatrix("UPPER-LEFT", $ABBR_HOME_TEAM,$HOME_SCORE,$HOME_MATRIX_POS,$HOME_TEAM_COLOR);
//updateScoreOnMatrix($HOME_TEAM_MATRIX, $ABBR_HOME_TEAM,$HOME_SCORE,$HOME_MATRIX_POS,$HOME_TEAM_COLOR);
updateScoreOnMatrix($HOME_TEAM_MATRIX, $ABBR_HOME_TEAM,$HOME_SCORE,$HOME_TEAM_SCORE_MATRIX,$HOME_TEAM_COLOR,$HOME_MATRIX_POS,$HOME_SCORE_MATRIX_POS);

//updateScoreOnMatrix("BOTTOM-LEFT", $ABBR_AWAY_TEAM,$AWAY_SCORE,$AWAY_MATRIX_POS,$AWAY_TEAM_COLOR);
//updateScoreOnMatrix($AWAY_TEAM_MATRIX, $ABBR_AWAY_TEAM,$AWAY_SCORE,$AWAY_MATRIX_POS,$AWAY_TEAM_COLOR);
updateScoreOnMatrix($AWAY_TEAM_MATRIX, $ABBR_AWAY_TEAM,$AWAY_SCORE,$AWAY_TEAM_SCORE_MATRIX,$AWAY_TEAM_COLOR,$AWAY_MATRIX_POS,$AWAY_SCORE_MATRIX_POS);
//renable while when we are done
enableMatrixToolOutput($HOME_TEAM_MATRIX);
enableMatrixToolOutput($AWAY_TEAM_MATRIX);
enableMatrixToolOutput($HOME_TEAM_SCORE_MATRIX);
enableMatrixToolOutput($AWAY_TEAM_SCORE_MATRIX);
//

//write the updated scores to our file
WriteSettingtoFile("HOME_SCORE",$HOME_SCORE,$pluginName);
WriteSettingtoFile("AWAY_SCORE",$AWAY_SCORE,$pluginName);

//we now have our new scores

//print_r($sportsScoresMessageParts);

//

//print_r($sportsScoresData);

lockHelper::unlock();
?>
