<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>
			Experimental CW Generator - Double
		</title>
	</head>
	<body>
<?PHP
	$rand = rand(9100,11000);
?>
		<iframe src="cw.php?cw=quick-latest"></iframe>
		<iframe src="cw.php?cw=quick-<?=$rand?>"></iframe>
		
	</body>
</html>