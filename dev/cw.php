<?PHP

// read in the ini file to make an array
/*
I need a 2D array to be drawn on screen out of divs with IDs of the clue
*/

define("SQUARE_SIZE", 29);
$sq = SQUARE_SIZE;
$ini = str_replace(".php", ".ini", basename($_SERVER['SCRIPT_NAME']));
if (!file_exists($ini)) {
	if (isset($argv)) {
		$test = array($argv[1], $argv[2]);
	} else {
		$test = preg_split('/-/', $_GET['cw']);
	}
	if ($test[0] != 'quick' && $test[0] != 'cryptic' && $test[0] != 'hc') {
		echo "Bad variable";
		exit;			
	} else if ($test[1] != 'latest' && !preg_match("/^[0-9]+$/", $test[1])) {
		echo "Bad variable";
		exit;
	}
	$ini = "ini/".$_GET['cw'].'.ini';
}


$crossword = parse_ini_file($ini, true);
$grid = array();
$max_x = 0;
$max_y = 0;
$output = "";
$grid = array();
$intersections = "var intersections = {};\n";
$lengths = "var lengths = {};\n";
$solutions = "var solutions = {};\n";
$words_in_clue = "var words_in_clue = {};\n";
$scripts = "";

$across = "";
$down = "";

foreach($crossword as $clue => $data) {
	//build the actual length
	/* TODO
	 * There's a problem here if the number of words in the solution
	 * exceeds the number of word-spaces in the grid.
	 * if count($extra)+1 > count(number of commas in length+1)
	 *   do a grid walk to find the furthest we can go from here
	 *   (i.e. stop and rewind two when we find a clue going in the
	 *    same direction)
	 * unless we munge the length reported in the clue instead...
	 */
	
	$display_length = $data['length'];
	if (is_numeric($data['length'])) {
		$length = $data['length'];
	} else {
		$length = 0;
		$tmp = $data['length'];
		while (substr($tmp, 0, strpos($tmp, ',')) > 0) {
			$length += $tmp;
			$data['ends'][$length] = true;
			$tmp = substr($tmp, strpos($tmp, ",")+1);
		}
		$length += $tmp;
	}
	$lengths .= "lengths[\"{$clue}\"] = {$length};\n";
	//get the words length from the clue
	if (preg_match("/\(([0-9,?]+)\)/", $data['clue'], $word_lengths)) {
		$data['word_boundaries'] = array();
		$word_lengths = preg_split("/,/", $word_lengths[1]);
		$traversed = 0;
		foreach($word_lengths as $word_length) {
			$traversed += $word_length;
			$data['word_boundaries'][] = $traversed;
		}
	}
	if (array_key_exists('solution', $data)) {
		//try to speed up the solutions / check all buttons
		/*
		for($i = 1; $i <= strlen($data['solution']); $i++) {
			$solutions .= "solutions[\"{$clue}-{$i}\"] = '".$data['solution'][$i-1]."';\n";
		}
		*/
		$solutions .= "solutions[\"{$clue}\"] = '".$data['solution']."';\n";
	}

	$extra = "";
	if (array_key_exists('extra', $data)) {
		$prexes = preg_split('/,/', $data['extra']);
		foreach($prexes as $prex) {
			$extra = ", '".$prex."'";
				
		}
	}
	$words_in_clue .= "words_in_clue[\"{$clue}\"] = ['{$clue}'{$extra}];\n";

		
	list($num, $dir, $let) = preg_split("/-/", $clue);
	$x = $data['x'];
	$y = $data['y'];
	$top = SQUARE_SIZE * $y;
	$left = SQUARE_SIZE * $x;
	switch($dir) {
	case 'across':
		$height = SQUARE_SIZE;
		$width = SQUARE_SIZE * $length;
		break;
	case 'down':
		$height = SQAURE_SIZE * $length;
		$width = SQUARE_SIZE;
		break;
	}
	$output .= <<< EOF
	<span style="top:{$top}px; left:{$left}px;" class="indicator">{$num}</span>
	<div class="word" id="{$clue}" style="top: {$top}px; left:{$left}px; height:{$height}px; width:{$width}px;">
	
EOF;
	$done = false;
	for($i = 0; $i < $length; $i++) {
		$letter = $i+1;
		$class = "active";
		if (array_key_exists('word_boundaries', $data) && in_array($letter, $data['word_boundaries']) && $letter != $length) {
			$class .= " end-".$dir;
		}
		$id = $clue."-".$letter;
		$clue_top = 0;
		$clue_left = 0;
		
		if (array_key_exists($top.":".$left, $grid)) {
			//$grid[$top.":".$left][] = $id;
			$intersections.= "intersections[\"{$grid[$top.":".$left][0]}\"] = \"{$id}\";\n";
			$intersections.= "intersections[\"{$id}\"] = \"{$grid[$top.":".$left][0]}\";\n";
		} else {
			$grid[$top.":".$left] = array($id);
		}	
		//if it's across we increment x, else increment y
		if (stristr($clue, 'across')) {
			$clue_top = 0;
			$clue_left = SQUARE_SIZE * $i;
			$left += SQUARE_SIZE;
			$x++;
			//check for max
			if ($x > $max_x) {
				$max_x = $x;
			}
		} else {
			$clue_top = SQUARE_SIZE * $i;
			$top += SQUARE_SIZE;
			$clue_left = 0;
			$y++;
			if ($y > $max_y) {
				$max_y = $y;
			}
		}
		$output .= <<< EOF
		<input maxlength="1" type="text" id="{$id}" class="{$class}" style="top:{$clue_top}px; left:{$clue_left}px;" onfocus="highlightWord('{$clue}', '{$letter}');"></input>

EOF;
	}
	$output .= <<< EOF
	</div>

EOF;
	$word = $data['clue'];
	if ($dir == 'across') {
		$across .= <<< EOF
	<div id="{$clue}-clue" class="clue">
		{$num} : {$word}
	</div>

EOF;
	} else if ($dir == 'down') {
		$down .= <<< EOF
	<div id="{$clue}-clue" class="clue">
		{$num} : {$word}
	</div>

EOF;
	}
}

$width =SQUARE_SIZE * ($max_x);
$height=SQUARE_SIZE * ($max_y);

$scripts = <<< EOF
	<script type="text/javascript">
	{$intersections}
	{$lengths}
	{$solutions}
	{$words_in_clue}
	$().ready(function() {
		$("input").keydown(inputBind);
		CrosswordData.active_letter = "";
		CrosswordData.active_word = "";
		CrosswordData.LEFT = 37;
		CrosswordData.RIGHT = 39;
		CrosswordData.UP = 38;
		CrosswordData.DOWN = 40;
		$("div#panel").css("width", document.width - {$sq} - {$width});
	});
	</script>

EOF;

$output = <<< EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>
			Experimental CW Generator
		</title>
		<link rel="stylesheet" type="text/css" href="cw.css"></link>
		<script type="text/javascript" src="jquery-1.4.2.js"></script>
		<script type="text/javascript" src="cw.js"></script>
{$scripts}
	</head>
	<body>
		<div id="panel" class="right">
			<div id="buttons">
				<button id="cheat" name="cheat" value="cheat" onClick="processOne('cheat');">Cheat</button>
				<button id="solution" name="solution" value="solution" onClick="processAll('cheat');">Solution</button>
				<button id="cheat" name="cheat" value="cheat" onClick="processOne('check');">Check</button>
				<button id="solution" name="solution" value="solution" onClick="processAll('check');">Check All</button>
				<button id="store" name="store" value="store" onClick="store();">Store</button>
			</div>
			<div id="active-clue">
				&nbsp;
			</div>
		</div>
		<div id="crossword" class="grid" style="width: {$width}px; height:{$height}px;">
{$output}
		</div>
		<div id="information" class="bottom" style="top:{$height}px;">
			<div id="across">
				<h3>Across</h3>
{$across}
			</div>
			<div id="down">
				<h3>Down</h3>
{$down}
			</div>
		</div>
	</body>
</html>

EOF;

//file_put_contents('experimental.html', $output);
echo $output;

