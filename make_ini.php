<?PHP

if (count($argv) < 3) {
	echo $argv[0]." type number\n";
	exit;
}

$type = $argv[1];
$cw = $argv[2];

function curl_request($url)
{
    $defaults = array(
        CURLOPT_POST => 0,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
    );
    
    $ch = curl_init();
    curl_setopt_array($ch, $defaults);
    if( ! $result = curl_exec($ch))
    {
        trigger_error(curl_error($ch));
        echo "ERROR";
    }
    curl_close($ch);
    return $result;
}

function make_ini($arr, $name, $url) {
	$tmp = <<<EOF
[meta]
url="{$url}"


EOF;
	foreach ($arr as $clue => $data) {
		$tmp .= '['.$clue."]\n";
		foreach($data as $key => $var) {
			$tmp .= $key.'='.$var."\n";
		}
		$tmp .= "\n";
	}
	file_put_contents($name, $tmp);
}

$url = "http://www.guardian.co.uk/crosswords/{$type}/{$cw}";

if (file_exists($cw)) {
	$file = file_get_contents($cw);
} else {
	$file = curl_request($url);
	$ret = file_put_contents("./".$cw, $file);
}


//get DIVs
if (!preg_match_all("/div id=\"([0-9]+-(across|down))\".*style=\"(.*?)\".*>/", $file, $matches)) {
	echo "Something's wrong, can't get the clues\n";
}

define("SQUARE_SIZE", 29);

$clues = array();
foreach($matches[1] as $key => $clue) {
	preg_match_all("/<input id=\"{$clue}-[0-9]+\"/", $file, $letters);
	preg_match("/<label id=\"{$clue}-clue\".*?<\/span>\s+(.*?)<\/label>/ms", $file, $cluetext);
	preg_match("/left: ([0-9]+)px; top: ([0-9]+)px;/", $matches[3][$key], $coords);
	preg_match_all("/solutions\[\"{$clue}-[0-9]+\"] = \"(.)\"/ms", $file, $solutions);
	$sol = "";
	foreach($solutions[1] as $l) {
		$sol .= $l;
	}
	$extra = "";
	if (preg_match_all("/words_for_clue\[\"{$clue}\"\] = \['{$clue}',(.*)\];/", $file, $words)) {
		$extra = str_replace("'","", $words[1][0]);
	}
	$clues[$clue] = array
	(
		'x' => $coords[1]/SQUARE_SIZE,
		'y' => $coords[2]/SQUARE_SIZE,
		'length' => count($letters[0]),
		'clue' => '"'.str_replace('"', '\"', $cluetext[1]).'"',
		'solution' => '"'.$sol.'"',
		
	);
	if ($extra != "") {
		$clues[$clue]['extra'] = $extra;
	}
}

//print_r($clues);

make_ini($clues, $type.'-'.$cw.'.ini', $url);
unlink($cw);


