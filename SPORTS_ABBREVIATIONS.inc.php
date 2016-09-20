<?php 



$SPORTS_ABBREVIATIONS_ARRAY= array(
							array(
									"SPORT" => "NFL",
									"SOURCE" => "http://sports.espn.go.com/nfl/bottomline/scores",
					
									"NAMES" => array(
										"Green Bay" =>"GB",
										"Seattle" => "SEA",
										"Minnesota" => "MN",
										"Atlanta" => "ATL",
										"Oakland" => "OAK"
										)
							),
							array(
									"SPORT" => "MLB",
									"SOURCE" => "http://sports.espn.go.com/nmlb/bottomline/scores",
					
									"NAMES" => array(
										"Milwaukee" =>"MB",
										"Chicago" => "CUBS"
									)
						)
		);


//function to read abbreviations

function getTeamAbbreviations($SPORT) {
	
	global $SPORTS_ABBREVIATIONS_ARRAY;
	
//	echo "getting abbreviations for sport: ".$SPORT."\n";
	$TEAM_ABBR_ARRAY = array();
	
	$teamIndex=0;
	
	foreach ($SPORTS_ABBREVIATIONS_ARRAY as $sport) {
		
		//print_r($sport);
		
		if($SPORT == $sport['SPORT']) {
		//	echo "We have a sport match: ".$SPORT."\n";
			
			foreach ($sport['NAMES'] as $key => $value) {
				
		//		echo "team name: ".$key." ".$value."\n";
				$TEAM_ABBR_ARRAY[$teamIndex]=array($key,$value);
				$teamIndex++;
				
				
			}
		}
		
	}
	return $TEAM_ABBR_ARRAY;
}


//get the abbreviation for a team name
function getTeamAbbreviationName($SPORT,$TEAM_READ) {

	$TEAMS = getTeamAbbreviations($SPORT);
	
	$teamIndex =0;
	
	for($teamIndex=0;$teamIndex<=count($TEAMS)-1;$teamIndex++) {
		
		if($TEAMS[$teamIndex][0] == $TEAM_READ) {
			return $TEAMS[$teamIndex][1];
		}
	}
	return null;
	//print_r($TEAMS);
	
}
function printTeamSelection($TEAMS,$TEAM_READ,$SELECT_NAME) {
	
//	echo "team read: ".$TEAM_READ."<br/> \n";
//	echo "selec name: ".$SELECT_NAME."<br/> \n";
//	print_r($TEAMS);
	
	echo "<select name=\"".$SELECT_NAME."\"> \n";
	
		for($i=0;$i<=count($TEAMS)-1;$i++) {
			
			//use the ABBR in the config file plugin
			if($TEAMS[$i][0] == $TEAM_READ) {
				echo "<option selected value=\"".$TEAMS[$i][0]."\">".$TEAMS[$i][0]."</option> \n";
			} else {
				echo "<option value=\"".$TEAMS[$i][0]."\">".$TEAMS[$i][0]."</option> \n";
			}
		}
	
	echo "</select> \n";
	
}
?>