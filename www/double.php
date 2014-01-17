<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>
			Experimental CW Generator - Double
		</title>
	</head>
	<body>
<?PHP
	$whens = array(
		'first' => 1,
		'latest' => 1,
		'random' => 1,
	);
	$rand = array(
		'quick' => rand(9100,11000),
		'cryptic' => rand(24500, 25000),
		'hc' => rand(1, 2)
	);
	$valid_types = array_keys($rand);
	$first_type = "quick";
	$first_when = "latest";
	$second_type = "quick";
	$second_when = "random";
	if (@$_GET['first_type'] && array_key_exists($_GET['first_type'], $rand)) {
		$first_type = $_GET['first_type'];
	}
	if (@$_GET['second_type'] && array_key_exists($_GET['second_type'], $rand)) {
		$second_type = $_GET['second_type'];
	}
	if (@$_GET['first_when'] && (
		is_numeric($_GET['first_when']) || array_key_exists($_GET['first_when'], $whens)
	)) {
		$first_when = $_GET['first_when'];
	}
	if (@$_GET['second_when'] && (
		is_numeric($_GET['second_when']) || array_key_exists($_GET['second_when'], $whens)
	)) {
		$second_when = $_GET['second_when'];
	}
	
	function get_random_for_type($type, $rand) {
		$rand = $rand[$type];
        $count = 0;
		while (!file_exists("../ini/{$type}-{$rand}.ini")) {
            if ($count >= 10) {
                echo "Can't find any inis";
                exit;
            }
            $count++;
			$rand = array(
				'quick' => rand(9100,11000),
				'cryptic' => rand(24500, 25000),
				'hc' => rand(1, 2)
			);
			$rand = $rand[$type];
		}
		return $rand;
	}
	
	if ($first_when == 'random') {
		$first_when = get_random_for_type($first_type, $rand);
	}
	$first = $first_type.'-'.$first_when;
	
	if ($second_when == 'random') {
		$second_when = get_random_for_type($second_type, $rand);
	}
	$second = $second_type.'-'.$second_when;

?>
		<iframe src="cw.php?cw=<?=$first?>" width="100%" height="1000px"></iframe>
		<iframe src="cw.php?cw=<?=$second?>" width="100%" height="1000px"></iframe>
		
	</body>
</html>