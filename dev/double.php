<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>
			Experimental CW Generator - Double
		</title>
	</head>
	<body>
<?PHP
	$rand = array(
		'quick' => rand(9100,11000),
		'cryptic' => rand(24500, 25000),
		'hc' => rand(1, 2)
	);
	$type = "quick";
	if ($_GET['type']) {
		$type = $_GET['type'];
	}
	$rand = $rand[$type];
?>
		<iframe src="cw.php?cw=quick-latest" width="100%" height="700px"></iframe>
		<iframe src="cw.php?cw=<?=$type?>-<?=$rand?>" width="100%" height="700px"></iframe>
		
	</body>
</html>