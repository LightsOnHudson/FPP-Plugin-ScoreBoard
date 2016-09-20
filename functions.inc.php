<?php

function printFontSizes($ELEMENT, $FONT_SIZE)


{

	global $PLUGINS,$pluginDirectory;

	$MAX_FONT_SIZE = 64;

	echo "<select name=\"".$ELEMENT."\">";


	for($i=0;$i<=$MAX_FONT_SIZE-1;$i++) {

		if($i == $FONT_SIZE) {

			echo "<option selected value=\"" . $i. "\">" . $i. "</option>";
		} else {
			echo "<option value=\"" . $i. "\">" . $i. "</option>";
		}
	}
	echo "</select>";
}




function printFontsInstalled($ELEMENT, $FONT)


{

	// this uses the fpp-matrix tools plugin to get the fonts that it can use!


	global $DEBUG,$PLUGINS,$pluginDirectory, $fpp_matrixtools_Plugin_Script, $fpp_matrixtools_Plugin;

	//  $fpp_matrixtools_Plugin = "fpp-matrixtools";
	// $fpp_matrixtools_Plugin_Script = "scripts/matrixtools";


	$fontsDirectory = "/usr/share/fonts/truetype/";

	//$FONTS_LIST_CMD = "/usr/bin/fc-list";

	//$FONT_LIST = system($FONTS_LIST_CMD);
	//if($DEBUG)
	//	print_r($FONT_LIST);

	// $FONTS_INSTALLED = directoryToArray($fontsDirectory, true);//, $recursive)($pluginDirectory);

	$MatrixToolsFontsCMD = $pluginDirectory."/".$fpp_matrixtools_Plugin . "/".$fpp_matrixtools_Plugin_Script. " --getfontlist";

	exec($MatrixToolsFontsCMD, $fontsList);
	//   print_r($fontsList);

	 
	// $FONTS_INSTALLED = explode(" ",$fontsList);

	//  print_r($FONTS_INSTALLED);
	//print_r($PLUGINS_READ);

	echo "<select name=\"".$ELEMENT."\">";


	for($i=1;$i<=count($fontsList)-1;$i++) {
		//	$FONTINFO = pathinfo($FONTS_INSTALLED[$i]);
		//         $FONTS_INSTALLED_TEMP = basename($FONTS_INSTALLED[$i],'.'.$FONTINFO['extension']);

		if($fontsList[$i] == $FONT) {
				
			echo "<option selected value=\"" . $FONT . "\">" . $FONT . "</option>";
		} else {
			echo "<option value=\"" . $fontsList[$i] . "\">" . $fontsList[$i] . "</option>";
		}
	}
	echo "</select>";
}

function matrixOutline($matrix,$color) {

	global $COLORS_PER_PIXEL,$FPPMM_BINARY,$DEBUG;

	$blockParts=getBlockData($matrix);

	print_r($blockParts);

	$topLineStart =0;
	$topLineEnd = $blockParts[2]/$blockParts[5];

	if($DEBUG) {
		echo "top start: ".$topLineStart."\n";
		echo "top end: ".$topLineEnd."\n";
	}
	if($DEBUG)
		echo "drawing top \n";

	for($i=$topLineStart;$i<=$topLineEnd;$i++) {

		$cmdDraw = $FPPMM_BINARY." -c ".$i." -s ".$color;

		//echo "cmd draw: ".$cmdDraw."\n";
		exec($cmdDraw);
		//	break;

	}
	if($DEBUG)
		echo "drawing right side \n";

	for($i=$topLineEnd;$i<=$topLineEnd*$blockParts[5];$i+=$topLineEnd) {

		$cmdDraw = $FPPMM_BINARY." -c ".$i." -s ".$color;
		//echo "cmd draw: ".$cmdDraw."\n";
		exec($cmdDraw);
	}

	$bottomRight = $topLineEnd*$blockParts[5];
	$bottomLeft = $bottomRight-$topLineEnd;

	if($DEBUG) {
		echo "drawing bottom right to left \n";
		echo "bottom right: ".$bottomRight."\n";
		echo "bottom left: ".$bottomLeft."\n";
	}

	for($i=$bottomRight;$i>=$bottomLeft;$i--) {
		$cmdDraw = $FPPMM_BINARY." -c ".$i." -s ".$color;
		//echo " bottom right to left cmd draw: ".$cmdDraw."\n";
		exec($cmdDraw);
	}

	if($DEBUG)
		echo "bottom left to top left \n";

	for($i=$bottomLeft+1;$i>=$topLineStart;$i-=$topLineEnd) {
		$cmdDraw = $FPPMM_BINARY." -c ".$i." -s ".$color;
		//echo " bottom left to top left cmd draw: ".$cmdDraw."\n";
		exec($cmdDraw);

	}
}


function getMatrixPosition($matrix,$team) {


	global $settings;
	$BLOCK_FOUND=false;
	//echo "getting blocks";

	logEntry("Getting settings for matrix: ".$matrix." for positioning of Team: ".$team);

	//$blocks = getBlockOutputs();
	//print_r($blocks);
	$blockIndex=0;
	$stringSize=0;
	$startChannel=0;
	$channelCount=0;
	$stringCount=0;
	//$blockOutput = array();


	//$BLOCK_FOUND=false;
	$blockParts=getBlockData($matrix);
	//print_r($blockParts);


	$channelCount=(int)$blockParts[2];
	$stringCount = (int)$blockParts[5];
	$strandCount = (int)$blockParts[6];

	if($channelCount > 0)
	{
		$BLOCK_FOUND=true;
	}
	if(!$BLOCK_FOUND) {
		logEntry("Could not find block info? was FPPD not reset?");
		lockHelper::unlock();
		exit(0);
	}
	//$channelCount=0;
	//print_r($blockParts);
	logEntry("ChannelCount: ".$channelCount);
	logEntry("StringCount: ".$stringCount);
	logEntry("Strands: ".$strandCount);

	if((int)$channelCount <= 0) {
		logEntry("Cannot calculate: channel count <=0");
		lockHelper::unlock();
		exit(0);

	}

	$PIXEL_COLORS_PER_PIXEL=3;

	$MATRIX_WIDTH = abs(($channelCount/$strandCount)/$PIXEL_COLORS_PER_PIXEL/$stringCount);


	logEntry("Matrix Model Width: ".$MATRIX_WIDTH);
	logEntry("Matrix Model Height: ".$stringCount);

	switch ($team) {

		case "HOME":
		//	$position = "0,0";
			$position = "0";
			break;
				
		case "AWAY":
			//get the half and add 1 more over
			$position = round(($MATRIX_WIDTH/2), 0, PHP_ROUND_HALF_UP)+1;
			//$position .= ",0";
			//$position = ($MATRIX_WIDTH/2).",0";
			break;
	}


	logEntry("Position for team: ".$team." ".$position);



	return $position;
}
function getBlockData($matrix) {

	global $settings;

	$blocksTmpFile = file_get_contents($settings['channelMemoryMapsFile']);

	$blocksTmp = explode("\n",$blocksTmpFile);
	//	print_r($blocksTmp);

	$blockParts=array();

	//return $blockParts;


	$BLOCK_FOUND = false;

	foreach($blocksTmp as $blockLine) {
		//break;
		//	echo "block line: ".$blockLine."\n";

		$blockParts = explode(",",$blockLine);

		//search through to find the one we need to position on
		if($blockParts[0] == $matrix) {
			logEntry("we have our matrix : ".$blockParts[0]);
			//	echo "block index: ".$blockIndex."\n";

			return $blockParts;
			//$BLOCK_FOUND=true;
			//break;
				
		} else {
			$blockIndex++;
		}


	}
	//$BLOCK_FOUND=false;


	return;

}

function get_string_between($string, $start, $end){
	$string = ' ' . $string;
	$ini = strpos($string, $start);
	if ($ini == 0) return '';
	$ini += strlen($start);
	$len = strpos($string, $end, $ini) - $ini;
	return substr($string, $ini, $len);
}

//get the text status from the data line

function getSportStatus($data) {

	$SPORT_TEXT = "";
	$SPORT_TEXT = get_string_between($data, "(", ")");
	return $SPORT_TEXT;
}
//get the score from the data line
function getSportScore($data, $teamName) {

	//echo "extracting team score for: ".$teamName. " from data: ".$data."\n";

	//home team NEW Score
	//get the position of the team
	$homeScoreTmpPos = strpos($data,$teamName);
	//echo "homesocre pos: ".$homeScoreTmpPos."\n";
	$homeScoreTmpLine = trim(substr($data, $homeScoreTmpPos + strlen($teamName)));

	//	echo "chopped line: ".$homeScoreTmpLine."\n";

	//split the line with spaces.. and take the first value if it is an integer.. if it is not - thenit is not a score
	//it could be the time that the game is on...will test this
	//strip extra white pace first
	$homeScoreTmpLine = preg_replace('!\s+!', ' ', $homeScoreTmpLine);
	$homeScoreParts = explode(" ",$homeScoreTmpLine);
	//print_r($homeScoreParts);

	if(is_numeric($homeScoreParts[0])) {
		//	echo "we have a valid score: ".$homeScoreParts[0]."\n";
		$NEW_SCORE = $homeScoreParts[0];

	} else {
		//	echo "we do not have a valid score from our parsing \n";
		$NEW_SCORE=null;
	}
	return $NEW_SCORE;
}

function updateMatrixMessage($matrix,$messageText,$color) {
	global $pluginDirectory,$fpp_matrixtools_Plugin,$fpp_matrixtools_Plugin_Script,$FONT,$FONT_PATH,$FONT_SIZE,$MAX_SCORE_DIGITS,$PIXELS_PER_SECOND;
	
	if($color =="") {
		$color = "white";
	}
	// zeros look like 8's replace with captial O

	
	logEntry( "Updating matrix action: ".$matrix." Message: ".$messageText." Color: ".$color. " FONT: ".$FONT);
	
	
	$UPDATE_SCORE_CMD  = $pluginDirectory."/";
	$UPDATE_SCORE_CMD .= $fpp_matrixtools_Plugin;
	$UPDATE_SCORE_CMD .= "/".$fpp_matrixtools_Plugin_Script;
	$UPDATE_SCORE_CMD .= " --blockname \"".$matrix."\"";
	$UPDATE_SCORE_CMD .= " --message \"".$messageText."\"";
	$UPDATE_SCORE_CMD .= " --font \"".$FONT_PATH.$FONT."\"";
	$UPDATE_SCORE_CMD .= " --fontsize ".$FONT_SIZE;
	//$UPDATE_SCORE_CMD .= " --position ".$pos;
	$UPDATE_SCORE_CMD .= " --color \"".$color."\"";
	$UPDATE_SCORE_CMD .= " --pixelspersecond ".$PIXELS_PER_SECOND;
	
	logEntry("UPDATE sCORE CMD: ".$UPDATE_SCORE_CMD);
	
	exec($UPDATE_SCORE_CMD,$updatScoreResultOutput);
	
}
//function updateScoreOnMatrix($matrix, $team,$score,$pos,$color) {
	function updateScoreOnMatrix($team_matrix, $team,$score,$score_matrix,$color,$name_pos="0,0",$score_pos="0,0", $FONT_SIZE) {
	//($AWAY_TEAM_MATRIX, $ABBR_AWAY_TEAM,$AWAY_SCORE,$AWAY_TEAM_SCORE_MATRIX,$AWAY_TEAM_COLOR);
	global $pluginDirectory,$fpp_matrixtools_Plugin,$fpp_matrixtools_Plugin_Script,$FONT,$FONT_PATH,$MAX_SCORE_DIGITS;

	if($color =="") {
		$color = "white";
	}
	// zeros look like 8's replace with captial O
	$score = preg_replace('[0]', 'O', $score);

	$score = str_pad($score, $MAX_SCORE_DIGITS, ' ', STR_PAD_LEFT);

	logEntry( "Updating matrix: ".$team_matrix." Team: ".$team." Color: ".$color. " FONT: ".$FONT);


	$UPDATE_MATRIX_CMD  = $pluginDirectory."/";
	$UPDATE_MATRIX_CMD .= $fpp_matrixtools_Plugin;
	$UPDATE_MATRIX_CMD .= "/".$fpp_matrixtools_Plugin_Script;
	$UPDATE_MATRIX_CMD .= " --blockname \"".$team_matrix."\"";
	$UPDATE_MATRIX_CMD .= " --message \"".$team."\"";//." ".$score."\"";
	$UPDATE_MATRIX_CMD .= " --font \"".$FONT_PATH.$FONT."\"";
	$UPDATE_MATRIX_CMD .= " --fontsize ".$FONT_SIZE;
	$UPDATE_MATRIX_CMD .= " --position ".$name_pos;
	$UPDATE_MATRIX_CMD .= " --color \"".$color."\"";

	logEntry("UPDATE NAME ON MATRIX CMD: ".$UPDATE_MATRIX_CMD);

	exec($UPDATE_MATRIX_CMD,$updateNameResultOutput);
	
	logEntry( "Updating matrix: ".$score_matrix." Team: ".$team." Color: ".$color. " FONT: ".$FONT);
	
	
	$UPDATE_MATRIX_CMD  = $pluginDirectory."/";
	$UPDATE_MATRIX_CMD .= $fpp_matrixtools_Plugin;
	$UPDATE_MATRIX_CMD .= "/".$fpp_matrixtools_Plugin_Script;
	$UPDATE_MATRIX_CMD .= " --blockname \"".$score_matrix."\"";
	$UPDATE_MATRIX_CMD .= " --message \"".$score."\"";//." ".$score."\"";
	$UPDATE_MATRIX_CMD .= " --font \"".$FONT_PATH.$FONT."\"";
	$UPDATE_MATRIX_CMD .= " --fontsize ".$FONT_SIZE;
	$UPDATE_MATRIX_CMD .= " --position ".$score_pos;
	$UPDATE_MATRIX_CMD .= " --color \"".$color."\"";
	
	logEntry("UPDATE SCORE ON MATRIX CMD: ".$UPDATE_MATRIX_CMD);
	
	exec($UPDATE_MATRIX_CMD,$updatScoreResultOutput);


}
function printSportsOptions($SPORTS_READ)


{

	global $SPORTS,$SPORTS_DATA_ARRAY;

	//$SPORTS_READ = explode(",",$SPORTS);

	echo "<select  name=\"SPORTS\">";


	for($i=0;$i<=count($SPORTS_DATA_ARRAY)-1;$i++) {

		if(in_array($SPORTS_DATA_ARRAY[$i][0],$SPORTS_READ)) {
				
			echo "<option selected value=\"" . $SPORTS_DATA_ARRAY[$i][0] . "\">" . $SPORTS_DATA_ARRAY[$i][0] . "</option>";
		} else {

			echo "<option value=\"" . $SPORTS_DATA_ARRAY[$i][0] . "\">" . $SPORTS_DATA_ARRAY[$i][0] . "</option>";
		}

	}
	echo "</select>";
}




//is fppd running?????
function isFPPDRunning() {
	$FPPDStatus=null;
	logEntry("Checking to see if fpp is running...");
        exec("if ps cax | grep -i fppd; then echo \"True\"; else echo \"False\"; fi",$output);

        if($output[1] == "True" || $output[1] == 1 || $output[1] == "1") {
                $FPPDStatus = "RUNNING";
        }
	//print_r($output);

	return $FPPDStatus;
        //interate over the results and see if avahi is running?

}
//get current running playlist
function getRunningPlaylist() {

	global $sequenceDirectory;
	$playlistName = null;
	$i=0;
	//can we sleep here????

	//sleep(10);
	//FPPD is running and we shoud expect something back from it with the -s status query
	// #,#,#,Playlist name
	// #,1,# = running

	$currentFPP = file_get_contents("/tmp/FPP.playlist");
	logEntry("Reading /tmp/FPP.playlist : ".$currentFPP);
	if($currentFPP == "false") {
		logEntry("We got a FALSE status from fpp -s status file.. we should not really get this, the daemon is locked??");
	}
	$fppParts="";
	$fppParts = explode(",",$currentFPP);
//	logEntry("FPP Parts 1 = ".$fppParts[1]);

	//check to see the second variable is 1 - meaning playing
	if($fppParts[1] == 1 || $fppParts[1] == "1") {
		//we are playing

		$playlistParts = pathinfo($fppParts[3]);
		$playlistName = $playlistParts['basename'];
		logEntry("We are playing a playlist...: ".$playlistName);
		
	} else {

		logEntry("FPPD Daemon is starting up or no active playlist.. please try again");
	}
	
	
	//now we should have had something
	return $playlistName;
}
function processSequenceName($sequenceName,$sequenceAction="NONE RECEIVED") {

	global $CONTROL_NUMBER_ARRAY,$PLAYLIST_NAME,$EMAIL,$PASSWORD,$pluginDirectory,$pluginName;
        logEntry("Sequence name: ".$sequenceName);

        $sequenceName = strtoupper($sequenceName);
	//$PLAYLIST_NAME= getRunningPlaylist();

	if($PLAYLIST_NAME == null) {
		$PLAYLIST_NAME = "FPPD Did not return a playlist name in time, please try again later";
	}
//        switch ($sequenceName) {

 //               case "SMS-STATUS-SEND.FSEQ":

                $messageToSend="";
		$gv = new GoogleVoice($EMAIL, $PASSWORD);

		//send a message to all numbers in control array and then delete them from new messages
		for($i=0;$i<=count($CONTROL_NUMBER_ARRAY)-1;$i++) {
			logEntry("Sending message to : ".$CONTROL_NUMBER_ARRAY[$i]. " that playlist: ".$PLAYLIST_NAME." is ACTION:".$sequenceAction);
			//get the current running playlist name! :)	

				//$gv->sendSMS($CONTROL_NUMBER_ARRAY[$i], "PLAYLIST EVENT: ".$PLAYLIST_NAME." Action: ".$sequenceAction);
				$gv->sendSMS($CONTROL_NUMBER_ARRAY[$i], "PLAYLIST EVENT: Action: ".$sequenceAction);
		
		}		
		logEntry("Plugin Directory: ".$pluginDirectory);
		//run the sms processor outside of cron
		$cmd = $pluginDirectory."/".$pluginName."/getSMS.php";

		exec($cmd,$output); 



}
function logEntry($data) {

	global $logFile,$myPid;

	

		$data = $_SERVER['PHP_SELF']." : [".$myPid."] ".$data;
	
	$logWrite= fopen($logFile, "a") or die("Unable to open file!");
	fwrite($logWrite, date('Y-m-d h:i:s A',time()).": ".$data."\n");
	fclose($logWrite);
}



function processCallback($argv) {
	global $DEBUG,$pluginName;
	
	
	if($DEBUG)
		print_r($argv);
	//argv0 = program
	
	//argv2 should equal our registration // need to process all the rgistrations we may have, array??
	//argv3 should be --data
	//argv4 should be json data
	
	$registrationType = $argv[2];
	$data =  $argv[4];
	
	logEntry("PROCESSING CALLBACK: ".$registrationType);
	$clearMessage=FALSE;
	
	switch ($registrationType)
	{
		case "media":
			if($argv[3] == "--data")
			{
				$data=trim($data);
				logEntry("DATA: ".$data);
				$obj = json_decode($data);
	
				$type = $obj->{'type'};
				logEntry("Type: ".$type);	
				switch ($type) {
						
					case "sequence":
						logEntry("media sequence name received: ");	
						processSequenceName($obj->{'Sequence'},"STATUS");
							
						break;
					case "media":
							
						logEntry("We do not support type media at this time");
							
						//$songTitle = $obj->{'title'};
						//$songArtist = $obj->{'artist'};
	
	
						//sendMessage($songTitle, $songArtist);
						//exit(0);
	
						break;
						
						case "both":
								
						logEntry("We do not support type media/both at this time");
						//	logEntry("MEDIA ENTRY: EXTRACTING TITLE AND ARTIST");
								
						//	$songTitle = $obj->{'title'};
						//	$songArtist = $obj->{'artist'};
							//	if($songArtist != "") {
						
						
						//	sendMessage($songTitle, $songArtist);
							//exit(0);
						
							break;
	
					default:
						logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
						exit(0);
						break;
	
				}
	
	
			}
	
			break;
			exit(0);
	
		case "playlist":

			logEntry("playlist type received");
			if($argv[3] == "--data")
                        {
                                $data=trim($data);
                                logEntry("DATA: ".$data);
                                $obj = json_decode($data);
				$sequenceName = $obj->{'sequence0'}->{'Sequence'};	
				$sequenceAction = $obj->{'Action'};	
                                                processSequenceName($sequenceName,$sequenceAction);
                                                //logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
                                        //      logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
			}

			break;
			exit(0);			
		default:
			exit(0);
	
	}
	

}
?>
