<?php

$COLORS = array("Red","Blue","Green");


function printColorSelect($SELECT_NAME,$COLOR_READ) {
	
	global $COLORS;
	
	echo "<select name=\"".$SELECT_NAME."\"> \n";
	
	for($i=0;$i<=count($COLORS)-1;$i++) {
			
		//use the ABBR in the config file plugin
		if($COLORS[$i] == $COLOR_READ) {
			echo "<option selected value=\"".$COLORS[$i] ."\">".$COLORS[$i] ."</option> \n";
		} else {
			echo "<option value=\"".$COLORS[$i] ."\">".$COLORS[$i] ."</option> \n";
		}
	}
	
	echo "</select> \n";
	
	
}


?>