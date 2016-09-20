<?php
//$DEBUG=true;
//$skipJSsettings=1;

include_once "/opt/fpp/www/common.php";
include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';
include_once 'SPORTS_ABBREVIATIONS.inc.php';
include_once 'COLORS.inc.php';

//FIXME
//need to re-initlize the score board scores fo rwhen there is a new game!!!
//maybe if the team name or something changes. then reinitizlize :)

$pluginName = "ScoreBoard";

$PLAYLIST_NAME="";
$MAJOR = "98";
$MINOR = "01";
$eventExtension = ".fevt";
$MAX_FONT_SIZE=48;
//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;
$SPORTS_TICKER_PLUGIN_NAME="SportsTicker";
$SPORTS_TICKER_SPORTS_FILE="SPORTS.inc.php";

$MATRIX_MESSAGE_PLUGIN_NAME = "MatrixMessage";
$fpp_matrixtools_Plugin = "fpp-matrixtools";
$fpp_matrixtools_Plugin_Script = "scripts/matrixtools";
$FPP_MATRIX_PLUGIN_ENABLED=false;

$FONT = "advanced_led_board-7.ttf";
$FONT_PATH = $pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/";

//the only font size that works is 9 or greater with this font
$FONT_SIZE = "9";

$MATRIX_FUNCTIONS_FILE = "MatrixFunctions.inc.php";

$logFile = $settings['logDirectory']."/".$pluginName.".log";

if(file_exists($pluginDirectory."/".$SPORTS_TICKER_PLUGIN_NAME."/".$SPORTS_TICKER_SPORTS_FILE))
{
	include_once($pluginDirectory."/".$SPORTS_TICKER_PLUGIN_NAME."/".$SPORTS_TICKER_SPORTS_FILE);
} else {
	logEntry("No sports file found to include.. Is the SportsTicker plugin installed?");
	echo "No sports file to be found..Is the SportsTicker plugin installed?";
	exit(0);
}

if(file_exists($pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/".$MATRIX_FUNCTIONS_FILE))
{
	include_once($pluginDirectory."/".$MATRIX_MESSAGE_PLUGIN_NAME."/".$MATRIX_FUNCTIONS_FILE);
	
} else {
	logEntry("Cannot load the MatrixMessage Functions. Is the MatrixMessage Plugin Installed?");
	echo "Cannot load the Matrix Message Functions. Please install the Matrix Message Plugin";
	exit(0);
}

$gitURL = "https://github.com/LightsOnHudson/FPP-Plugin-Scoreboard.git";
//createSMSSequenceFiles();
$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";

if(isset($_POST['submit']))
{
	//$SPORTS =  implode(',', $_POST["SPORTS"]);
	
	//print_r($_POST);
	foreach ($_POST as $key => $value) {
	
	//	echo "Key: ".$key. " ".$value."\n";
		
		//do not save the submit ooption
		if(trim(strtoupper($key)) == "SUBMIT") {
			continue;
		}
		if(is_array($value)) {
		//	echo "ARRAY: ".print_r(${value});
			WriteSettingToFile($key,urlencode(implode(',',$value)),$pluginName);
		} else
		
	 		WriteSettingToFile($key,urlencode($value),$pluginName); 	
	}
	

}


sleep(1);

//print_r($pluginSettings);

//THIS IS O COOL!
//set the variable names as necessary??? do we even need to do this???

foreach ($pluginSettings as $key => $value) {
	
//	echo "Key: ".$key." " .$value."\n";
	
	${$key} = urldecode($value);
	
}

//print_r($_POST);
if($_POST['INITIALZE_SCORES'] == "on" || $_POST['INITIALZE_SCORES'] == "1" || $_POST['INITIALZE_SCORES'] == 1) {

	logEntry("Re-initializing the scores");
	
	$HOME_SCORE="";
	$AWAY_SCORE="";
	
	WriteSettingToFile("HOME_SCORE",urlencode($HOME_SCORE),$pluginName);
	WriteSettingToFile("AWAY_SCORE",urlencode($AWAY_SCORE),$pluginName);
	//logEntry("Re initizling scores");
}

//logEntry("home score: ".$HOME_SCORE);
//logEntry("Away score: ".$AWAY_SCORE);

//print_r($SPORTS);


	//$SPORTS = urldecode($pluginSettings['SPORTS']);
	
	$SPORTS_READ = explode(",",$SPORTS);
	
	//print_r($SPORTS_READ);
	
	

?>

<html>
<head>
</head>

<div id="<?echo $pluginName;?>" class="settings">
<fieldset>
<legend><?echo $pluginName;?> Support Instructions</legend>

<p>Known Issues:
<ul>
<li>NONE</li>
</ul>

<p>Configuration:
<ul>
<li>Configure your Matrixes for TOP and BOTTOM</li>
<li>Configure your Away and Home Teams from the drop down</li>
<li>Configure the Sports Ticker Plugin for the Sport you want to use</li>
<li>Configure the ScoreBoard plugin for the Sport you want to display (NFL, MLB, etc)</li>
</ul>
<ul>
<li>Add the crontabAdd options to your crontab to have the sms run every X minutes to process commands</li>

</ul>

<p>DISCLAIMER:
<ul>
<li>The Author and supporters of this plugin are NOT responsible for SMS charges that may be incurred by using this plugin</li>
<li>Check with your mobile provider BEFORE using this to ensure your account status</li>
</ul>


<form method="post" action="http://<? echo $_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT']?>/plugin.php?plugin=<?echo $pluginName;?>&page=plugin_setup.php">


<?
//will add a 'reset' to this later

echo "<input type=\"hidden\" name=\"LAST_READ\" value=\"".$LAST_READ."\"> \n";
echo "<input type=\"hidden\" name=\"FONT\" value=\"".$FONT."\"> \n";
echo "<input type=\"hidden\" name=\"FONT_SIZE\" value=\"".$FONT_SIZE."\"> \n";
echo "<input type=\"hidden\" name=\"HOME_SCORE\" value=\"".$HOME_SCORE."\"> \n";

echo "<input type=\"hidden\" name=\"AWAY_SCORE\" value=\"".$AWAY_SCORE."\"> \n";


$restart=0;
$reboot=0;

echo "<p/> \n";
echo "Re initialize Scores: ";


	echo "<input type=\"checkbox\" name=\"INITIALZE_SCORES\"> \n";
	//PrintSettingCheckbox("Radio Station", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");

echo "<p/> \n";

echo "ENABLE PLUGIN: ";

PrintSettingCheckbox("Scoreboard", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");


echo "<p/> \n";

echo "MATRIX FONT (Installed inside MatrixMessage Plugin): ";

echo "Font:  \n";
printFontsInstalled("FONT",$FONT);

echo "<p/> \n";
echo "Font Size: \n";
printFontSizes("FONT_SIZE",$FONT_SIZE);




echo "<p/> \n";
echo "Sport Scores to Display: ";
printSportsOptions($SPORTS_READ);


echo "<p/> \n";

echo "Home Team Name Location: \n";
PrintMatrixList("HOME_TEAM_MATRIX",$HOME_TEAM_MATRIX);

echo " ";

echo "Home Team Score Location: \n";
PrintMatrixList("HOME_TEAM_SCORE_MATRIX",$HOME_TEAM_SCORE_MATRIX);

echo "<p/> \n";
echo "Away Team Name Location: \n";
PrintMatrixList("AWAY_TEAM_MATRIX",$AWAY_TEAM_MATRIX);

echo " ";
echo "Away Team Score Location: \n";
PrintMatrixList("AWAY_TEAM_SCORE_MATRIX",$AWAY_TEAM_SCORE_MATRIX);

//print_r($SPORTS_ABBREVIATIONS);
$TEAMS = getTeamAbbreviations($SPORTS);
//print_r($TEAMS);

echo "<p/> \n";
echo "Home Team: \n";
printTeamSelection($TEAMS,$HOME_TEAM,"HOME_TEAM");

echo " \n";
echo "Home Team Color: \n";

printColorSelect("HOME_TEAM_COLOR",$HOME_TEAM_COLOR);

echo "<p/> \n";
echo "Away Team: \n";
printTeamSelection($TEAMS,$AWAY_TEAM,"AWAY_TEAM");

echo "  \n";
echo "Away Team Color: \n";

printColorSelect("AWAY_TEAM_COLOR",$AWAY_TEAM_COLOR);

echo "<p/> \n";

echo "Display Game Status (FINAL, 3rd QTR, ETC): ";

PrintSettingCheckbox("DISPLAY GAME STATUS", "DISPLAY_GAME_STATUS", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");

?>
<p/>
<input id="submit_button" name="submit" type="submit" class="buttons" value="Save Config">
<?
 if(file_exists($pluginUpdateFile))
 {
 	//echo "updating plugin included";
	include $pluginUpdateFile;
}
?>
</form>




<p>To report a bug, please file it against the sms Control plugin project on Git:<? echo $gitURL;?> 
</fieldset>
</div>
<br />
</html>
