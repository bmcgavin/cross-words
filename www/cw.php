<?PHP

// read in the ini file to make an array
/*
I need a 2D array to be drawn on screen out of divs with IDs of the clue
*/

define("SQUARE_SIZE", 29);
$sq = SQUARE_SIZE;
$test = array('quick', 'latest');

if (isset($_GET) || isset($argv)) {
	if (isset($argv)) {
		$test = array($argv[1], $argv[2]);
	} else if (array_key_exists('cw', $_GET)) {
		$test = preg_split('/-/', $_GET['cw']);
	}
	if ($test[0] != 'quick' && $test[0] != 'cryptic' && $test[0] != 'hc') {
		echo "Bad variable";
		exit;			
	} else if ($test[1] != 'latest' && !preg_match("/^[0-9]+$/", $test[1])) {
		echo "Bad variable";
		exit;
	}
	$_GET['cw'] = join($test, '-');
	$ini = "../ini/".$_GET['cw'].'.ini';
}


$crossword = parse_ini_file($ini, true);
$meta = array();
if (array_key_exists('meta', $crossword)) {
	$meta = $crossword['meta'];
	unset($crossword['meta']);
}
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

$clues = array_keys($crossword);

for ($indexomatic = 0; $indexomatic < count($clues); $indexomatic++) {
    $clue = $clues[$indexomatic];
    $data = $crossword[$clue];
	$data['clue'] = str_replace(array("\xe2\x80\x94", "\xe2\x80\x93"), '-', $data['clue']);
	$data['clue'] = str_replace('?', '?', $data['clue']);
	$data['clue'] = str_replace('’', '\'', $data['clue']);
	$data['clue'] = str_replace("\xc2\xad", '', $data['clue']);
	$data['clue'] = str_replace("\xc2\xa0", ' ', $data['clue']);
	$data['clue'] = str_replace("\xe2\x80\xa6", '...', $data['clue']);
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

    $extra_lengths = array();
    if (array_key_exists('extra', $data)) {
        $extras = preg_split("/,/", $data['extra']);
        foreach($extras as $extra_clue) {
            $extra_lengths[$extra_clue] = $crossword[$extra_clue]['length'];
        }
    }

	//get the words length from the clue
	if (preg_match_all("/\(([0-9,?]+)\)/", $data['clue'], $word_lengths)) {
        if (!array_key_exists('word_boundaries', $data)) {
            $data['word_boundaries'] = array();
        }
		$word_lengths = preg_split("/,/", $word_lengths[1][0]);
		$traversed = 0;
        $current_clue = $clue;
		foreach($word_lengths as $word_length) {
			$traversed += $word_length;
            if ($traversed > $crossword[$current_clue]['length']) {
                $traversed -= $crossword[$current_clue]['length'];
                foreach($extra_lengths as $extra_clue => $extra_length) {
                    if ($traversed > $extra_length) { 
                        $traversed -= $extra_length;
                    } else {
                        $current_clue = $extra_clue;
                        array_shift($extra_lengths);
                        break;
                    }
                    
                }
                if (!array_key_exists('word_boundaries', $crossword[$current_clue])) {
                    $crossword[$current_clue]['word_boundaries'] = array();
                }

                $crossword[$current_clue]['word_boundaries'][] = $traversed;

            }
            if ($clue == $current_clue) {
                $data['word_boundaries'][] = $traversed;
            }
		}
        echo "<!-- $clue : " . print_r($data, true) . "-->";
	}
	//get the words length from the clue
	if (preg_match_all("/\(([0-9\-?]+)\)/", $data['clue'], $word_lengths)) {
        if (!array_key_exists('word_hyphens', $data)) {
            $data['word_hyphens'] = array();
        }
		$word_lengths = preg_split("/-/", $word_lengths[1][0]);
		$traversed = 0;
        $current_clue = $clue;
		foreach($word_lengths as $word_length) {
			$traversed += $word_length;
            if ($traversed > $crossword[$current_clue]['length']) {
                $traversed -= $crossword[$current_clue]['length'];
                foreach($extra_lengths as $extra_clue => $extra_length) {
                    if ($traversed > $extra_length) { 
                        $traversed -= $extra_length;
                    } else {
                        $current_clue = $extra_clue;
                        array_shift($extra_lengths);
                        break;
                    }
                    
                }
                if (!array_key_exists('word_hyphens', $crossword[$current_clue])) {
                    $crossword[$current_clue]['word_hyphens'] = array();
                }

                $crossword[$current_clue]['word_hyphens'][] = $traversed;

            }
            if ($clue == $current_clue) {
                $data['word_hyphens'][] = $traversed;
            }
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
			$extra .= ", '".$prex."'";
				
		}
	}
	$words_in_clue .= "words_in_clue[\"{$clue}\"] = ['{$clue}'{$extra}];\n";

		
    $dir = null;
	list($num, $dir) = preg_split("/-/", $clue);
    if ($dir == null) {
        error_log('No direction in \'' . $clue . '\'');
        continue;
    }
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
		$height = SQUARE_SIZE * $length;
		$width = SQUARE_SIZE;
		break;
    default:
        error_log('Bad direction in \'' . $clue . '\'');
        continue;
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
		if (array_key_exists('word_hyphens', $data) && in_array($letter, $data['word_hyphens']) && $letter != $length) {
			//OUTPUT A HYPHEN DIV
			if ($dir == 'across') {
				$style = "left:".($clue_left+(SQUARE_SIZE-5))."px";
			} else if ($dir == 'down') {
				$style = "top:".($clue_top+(SQUARE_SIZE-5))."px";
			}
			$output .= <<< EOF
			<div class="hyphen-{$dir}" style="{$style};">&nbsp;</div>

EOF;
		}
		
	}
	$output .= <<< EOF
	</div>

EOF;
	$word = $data['clue'];
	if ($dir == 'across') {
		$across .= <<< EOF
	<div id="{$clue}-clue" class="clue" onClick="highlightWord('{$clue}');">
		{$num} : {$word}
	</div>

EOF;
	} else if ($dir == 'down') {
		$down .= <<< EOF
	<div id="{$clue}-clue" class="clue" onClick="highlightWord('{$clue}');">
		{$num} : {$word}
	</div>

EOF;
	}
}

$width =SQUARE_SIZE * ($max_x);
$height=SQUARE_SIZE * ($max_y);
$panel_start = $height + SQUARE_SIZE;

$scripts = <<< EOF
	<script type="text/javascript">
	{$intersections}
	{$lengths}
	{$solutions}
	{$words_in_clue}
	$().ready(function() {
		$("input").keyup(inputBind);
		CrosswordData.active_letter = "";
		CrosswordData.active_word = "";
		CrosswordData.LEFT = 37;
		CrosswordData.RIGHT = 39;
		CrosswordData.UP = 38;
		CrosswordData.DOWN = 40;
		$("div#panel").css("width", document.width - {$sq} - {$width});
		$('input[class*="end"]').each(function(id, element) {
			if (intersections[element.id]) {
				tmp = element.id.split('-');
				$('input#'+intersections[element.id]).addClass('end-'+tmp[1]);
			}
		});
	});
	</script>

EOF;

$url = '#';
if (array_key_exists('url', $meta)) {
	$url = $meta['url'];
}

$nav = '';
if ($test[1] == 'latest') {
    $test[1] = basename($url);
}
if (file_exists('../ini/' . $test[0] . '-' . ($test[1] - 1) . '.ini')) {
    $nav .= '&nbsp;<a href="/cw.php?cw=' . $test[0] . '-' . ($test[1] - 1) . '">Previous</a>';
} else if ($test[0] == 'cryptic' && file_exists('../ini/' . $test[0] . '-' . ($test[1] - 2) . '.ini')) {
    $nav .= '&nbsp;<a href="/cw.php?cw=' . $test[0] . '-' . ($test[1] - 2) . '">Previous</a>';
}
if (file_exists('../ini/' . $test[0] . '-' . ($test[1] + 1) . '.ini')) {
    $nav .= '&nbsp;<a href="/cw.php?cw=' . $test[0] . '-' . ($test[1] + 1) . '">Next</a>';
} else if ($test[0] == 'cryptic' && file_exists('../ini/' . $test[0] . '-' . ($test[1] + 2) . '.ini')) {
    $nav .= '&nbsp;<a href="/cw.php?cw=' . $test[0] . '-' . ($test[1] + 2) . '">Next</a>';
}
$output = <<< EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>
			Experimental CW Generator
		</title>
		<link rel="stylesheet" type="text/css" href="cw.css"></link>
		<!--<script type="text/javascript" src="jquery-1.4.2.js"></script>-->
		<script type="text/javascript" src="jquery-1.11.0.min.js"></script>
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
				<!-- <button id="store" name="store" value="store" onClick="store();">Store</button> -->
			</div>
			<div id="active-clue">
				&nbsp;
			</div>
			<div id="active-word">
				&nbsp;
			</div>
		</div>
		<div id="padding">
			<p class="small">Sourced from <a href="{$url}">{$url}</a>
{$nav}
            </p>
			<div id="crossword" class="grid" style="width: {$width}px; height:{$height}px;">
{$output}
			</div>
			<div id="information" class="bottom" style="top:{$panel_start}px;">
				<div id="across">
					<h3>Across</h3>
{$across}
				</div>
				<div id="down">
					<h3>Down</h3>
{$down}
				</div>
			</div>
		</div>
	</body>
</html>

EOF;

//file_put_contents('experimental.html', $output);
echo $output;

