#!/usr/bin/php
<?php

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
define('LOCK_SUFFIX', '.lock');
$FPPMM_BINARY = "/opt/fpp/bin/fppmm";


$SPORTS_TICKER_PLUGIN_NAME="SportsTicker";
$SPORTS_TICKER_SPORTS_FILE="SPORTS.inc.php";

$MATRIX_MESSAGE_PLUGIN_NAME = "MatrixMessage";
$fpp_matrixtools_Plugin = "fpp-matrixtools";
$fpp_matrixtools_Plugin_Script = "scripts/matrixtools";
$FPP_MATRIX_PLUGIN_ENABLED=false;

$PIXELS_PER_SECOND=20;

$COLORS_PER_PIXEL=3;


$MAX_SCORE_DIGITS=2;

$FONT = "advanced_led_board-7.ttf";
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



if($ENABLED != "on" && $ENABLED != "1") {
	logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use & Restart FPPD Daemon");
	lockHelper::unlock();
	exit(0);
}
if(file_exists($pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/".$MATRIX_FUNCTIONS_FILE))
{
	include_once($pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/".$MATRIX_FUNCTIONS_FILE);

} else {
	logEntry("Cannot load the MatrixMessage Functions. Is the MatrixMessage Plugin Installed?");
	if($DEBUG) {
		echo "Cannot load the Matrix Message Functions. Please install the Matrix Message Plugin";
	}
	lockHelper::unlock();
	exit(0);
}



$options = getopt("T:C:");

if($DEBUG)
	print_r($options);

$scoreACTION="";

if(strtoupper(trim($options["T"])) == "OUCHDOWN" || strtolower(trim($options["t"])) == "ouchdown") {
	$scoreACTION="TOUCHDOWN";
	
	
}

if($DEBUG)
	echo "score action: ".$scoreACTION."\n";

switch ($scoreACTION) {
	
	case "TOUCHDOWN":
		$messageText ="TOUCHDOWN";
		
		break;
	
	case "GOAL":
		
		break;
		
	 default:
		
		$messageText="THANK YOU";
		break;
}

//send the message to the bottom matrix


$color ="blue";

//updateMatrixMessage($MATRIX_BOTTOM,$messageText,$color);


//disableMatrixToolOutput($MATRIX_BOTTOM);

$MATRIX_FULL = urldecode(ReadSettingFromFile("MATRIX",$MATRIX_MESSAGE_PLUGIN_NAME));

if($DEBUG)
	echo "matrix full: ".$MATRIX_FULL."\n";

$color = 255;
if($DEBUG)
	echo "Disabling top and bottom matrix \n";

disableMatrixToolOutput($MATRIX_BOTTOM);

disableMatrixToolOutput($MATRIX_TOP);

if($DEBUG)
	echo "Clearing full matrix \n";

clearMatrix($MATRIX_FULL);

if($DEBUG)
	echo "Enabling full matrix: \n";

enableMatrixToolOutput($MATRIX_FULL);

if($DEBUG)
	echo "drawing outline on full matrix \n";


matrixOutline($MATRIX_FULL,$color);

//clearMatrix($matrix="");

$FONT_SIZE="10";

if($DEBUG)
	echo "sending text o full matrix \n";

updateMatrixMessage($MATRIX_FULL,$messageText,$color);

if($DEBUG)
	echo "disabling full matrix \n";

if($DEBUG)
	disableMatrixToolOutput($MATRIX_FULL);

if($DEBUG)
	echo "Clearing Top matrix \n";

clearMatrix($MATRIX_TOP);

if($DEBUG)
	echo "clearing bottom matrix \n";

clearMatrix($MATRIX_BOTTOM);

if($DEBUG)
	echo "updating scores \n";

$FONT_SIZE = $pluginSettings['FONT_SIZE'];

updateScoreOnMatrix($MATRIX_TOP, $ABBR_HOME_TEAM,$HOME_SCORE,$HOME_MATRIX_POS,$HOME_TEAM_COLOR);

updateScoreOnMatrix($MATRIX_TOP, $ABBR_AWAY_TEAM,$AWAY_SCORE,$AWAY_MATRIX_POS,$AWAY_TEAM_COLOR);


if($DEBUG)
	echo "re enabling top matrix \n";

enableMatrixToolOutput($MATRIX_TOP);

if($DEBUG)
	echo "re enabling bottom matrix \n";

enableMatrixToolOutput($MATRIX_BOTTOM);



lockHelper::unlock();

?>