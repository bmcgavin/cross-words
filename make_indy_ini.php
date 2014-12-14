<?php
$debug = false;
$offset=0x22b;

$length = 15;


$fh = fopen($argv[1], 'rb');
$offsets = array(
    0x68, 0x149
);
$lengths = array(
    $length * $length, $length * $length
);
$output = array(
    '', ''
);

foreach($offsets as $i => $v) {
    fseek($fh, $v);
    $output[$i] = fread($fh, $lengths[$i]);
}

fseek($fh, $offset);

/*
 * 0x434a06 header
 * reveal text
 * 0x05
 * check txt 
 * 0x0d
 * reveal letter text 
 * 0x00
 * 0x2f
 * Congrats text
 * 0x02
 * 0x0f - width 0x0f = 15, 0x0d = 13 - both grid and clue must fit
 * 0x0f - height
 * 0x01
 * 0x17
 * 0x00
 * 0x00000000
 * 0x00000000
 * 0x00000000
 * 0x00c0c0c0
 * 0x00ffff00
 * Grid layout :
 * From 0x68 for width*height, ? is a space and # is a block
 * Clue layout :
 * From 0x68 + width * height for width * height,
 * bitmasks!
 * 00000000 empty (white or black determined by grid)
 *        1 clue start
 *       1  separator left
 *      1   separator top
 *     1    make it come out green!
 *    1     white again
 *   1      circle!
 *  1       Won't load
 * 1        white
 * 0x01
 * answers
 * Fills out the ? in grid layout from left to right, top to bottom
 * 0x0200ff00
 * 0x000b0103
 * 0x000000c8
 * 0x00000005
 * 0xnum bytes to A of Across (includes 3x 0x2e)
 *
 * Clue format : 2 byte header (checksum?)
 * 1 byte number length BCD
 * number
 * 2 byte clue length BCD
 * clue
 * Some bytes between across and down.
 */

$answers=0;
$setter=1;
$clues=2;
$data = array(
    '',
    '',
    array(),
);
$pointer=0;
$clue = '';
$across = true;
$acrossClues = array();
$downClues = array();
$multis = array();
while (false !== ($b = fgetc($fh))) {
    if ($pointer == $answers) {
        if (bin2hex($b) == '02') {
            //skip 15
            fread($fh, 15);
            $pointer = $setter;
            continue;
        }
    }
    if ($pointer == $setter) {
        $setterLength = hexdec(bin2hex($b));
        fread($fh, $setterLength+3);
        $b1 = fgetc($fh);
        if ($debug)echo 'b1 : ' . hexdec(bin2hex($b1)) . PHP_EOL;
        if (bin2hex($b1) == '0b') {
            if($debug)echo 'skipping three times';
            fgetc($fh);
            fgetc($fh);
            fgetc($fh);
        }
        $pointer = $clues;
        continue;
    }
    if ($pointer == $clues) {
        if (bin2hex($b) == '04') {
            if (fgetc($fh) == 'D') {
                //Down
                $across = false;
                fread($fh, 8);
            } else {
                if($debug)echo ftell($fh) . PHP_EOL;
                fseek($fh, -1, SEEK_CUR);
                if($debug)echo ftell($fh) . PHP_EOL;
            }
        }
        if($debug)echo 'b : ' . hexdec(bin2hex($b)) . PHP_EOL;
        if (bin2hex($b) == '8b' 
         || bin2hex($b) == '81') {
            if ($debug) echo 'skipping three times';
            fgetc($fh);
            fgetc($fh);
            $b = fgetc($fh);
        }
        $currentClue = array();
        $currentClue['header'] = $b . fgetc($fh);
        $currentClue['numLength'] = fgetc($fh);
        $currentClue['num'] = fread($fh, hexdec(bin2hex($currentClue['numLength'])));
        $currentClue['clueLength'] = fread($fh, 2);
        $currentClue['clue'] = fread($fh, hexdec(bin2hex($currentClue['clueLength'])));
        if (strpos($currentClue['num'], '/') !== false) {
            $tmp = explode('/', $currentClue['num']);
            $key = array_shift($tmp);
            foreach($tmp as $k) {
                $multis[$k.'-'. (($across) ? 'across' : 'down')] = $key.'-'. (($across) ? 'across' : 'down');
            }
        }
        if ($debug) {
            foreach($currentClue as $k => $v) {
                echo $k . '=' . bin2hex($v) . PHP_EOL;
            }
        }
        $data[$pointer][] = $currentClue['clue'];
        if ($across) {
            $acrossClues[] = $currentClue['clue'];
        } else {
            $downClues[] = $currentClue['clue'];
        }

        $clue = '';
    } else {
        $data[$pointer] .= $b;
    }
}

$clueTexts = $data[$clues];

$starts = '';
foreach(str_split($output[1]) as $c) {
    $starts .= substr(bin2hex($c), 1, 1);
}


$grid = str_split($output[0], 1);
$answerChar = str_split($data[$answers], 1);
foreach($grid as $i => $char) {
    if ($char == '?') {
        $grid[$i] = array_shift($answerChar);
    }
}

if ($debug)print_r($multis);

$grid2 = join("", $grid);

if ($debug)print_r(chunk_split($grid2, $length, PHP_EOL));

$across = array();
$down = array();
$markers = str_split($starts, 1);
//print_r($markers);
$row = 0;
$clue = 1;
if ($debug)print_r($grid);
foreach($grid as $i => $letter) {
    if ($i > 0 && $i % $length == 0) {
        $row++;
        //echo "$i : row to $row\n";
    }
    if ($markers[$i] & 0x01 == 0x01) {
        $found = false;
        if ($debug)echo "i:$i\n";
        if ($debug)echo "length:$length\n";
        if ($debug)echo "i % length - 1:" . $i % ($length - 1) . "\n";
        if ($debug)echo "grid[i+1]:{$grid[$i+1]}\n";
        if ($debug)echo "i % length : ". $i % $length . "\n";
        if (array_key_exists($i + 1, $grid)
         && $grid[$i+1] != '#'
         && (($i % $length >= 1 && $grid[$i-1] == '#') || $i % $length == 0)
         && $i % $length != ($length - 1)) {
        //if (($i % ($length - 1) != 0 || $i == 0)
        // && $grid[$i+1] != '#'
        // && (($i > 1 && $grid[$i-1] == '#') || $i < 1)) {
            $cluePos = array(
                'x' => $i % $length,
                'y' => $row,
            );
            if (array_key_exists($clue.'-across', $multis)) {
                $cluePos['clue'] = 'See ' . $multis[$clue.'-across'];
            } else {
                $cluePos['clue'] = array_shift($acrossClues);
            }
            $across[$clue] = $cluePos;
            $found = true;
            if ($debug)echo "Across : ";
        } else {
            if ($debug)echo "Doesn't think it's an across:\n";
        }
        if (array_key_exists($i + $length, $grid)
         && $grid[$i+$length] != '#'
         && (($i > $length && $grid[$i-$length] == '#') || $i < $length)) {
            $cluePos = array(
                'x' => $i % $length,
                'y' => $row,
            );
            if (array_key_exists($clue.'-down', $multis)) {
                $cluePos['clue'] = 'See ' . $multis[$clue.'-down'];
            } else {
                $cluePos['clue'] = array_shift($downClues);
            }
            $down[$clue] = $cluePos;
            $found = true;
            if ($debug) echo "Down : ";
        }
        if ($found) {
            if ($debug) echo "$clue : $row, " . $i % ($length) . "\n";
            $clue++;
        }
    }

}

//print_r($down);
//print_r($across);
//

$clues = array();

foreach($down as $index=> $pos) {
    $solution = "";
    $x = $pos['x']; $y = $pos['y'];
    $mark = $x + ($length * $y);
    while ($grid[$mark] != '#') {
        $solution .= $grid[$mark];
        $mark += $length;
        if ($mark % ($length) == 0 && $x != 0) {
            break;
        }
        if ($mark >= count($grid)) {
            break;
        }
    }
    $clue = array(
        'x' => $x,
        'y' => $y,
        'length' => strlen($solution),
        'clue' => '"' . str_replace('"', '\"', $pos['clue']) . '"',
        'solution' => '"' . $solution . '"'
    );
    if (array_key_exists($index.'-down', $multis)) {
        $clue['extra'] = $multis[$index.'-down'];
    }
    $clues[$index.'-down'] = $clue;
}

foreach($across as $index=> $pos) {
    $solution = "";
    $x = $pos['x']; $y = $pos['y'];
    $mark = $x + ($length * $y);
    while ($grid[$mark] != '#') {
        $solution .= $grid[$mark];
        $mark += 1;
        if ($mark % $length == 0) {
            break;
        }
        if ($mark >= count($grid)) {
            break;
        }
    }
    $clue = array(
        'x' => $x,
        'y' => $y,
        'length' => strlen($solution),
        'clue' => '"' . str_replace('"', '\"', $pos['clue']) . '"',
        'solution' => '"' . $solution . '"'
    );
    if (array_key_exists($index.'-across', $multis)) {
        $clue['extra'] = $multis[$index.'-across'];
    }
    $clues[$index.'-across'] = $clue;
}

$ini = '';
foreach($clues as $index => $data) {
    $ini.= '[' . $index . ']' . PHP_EOL;
    foreach($data as $key => $value) {
        $ini .= $key . '=' . $value . PHP_EOL;
    }
    $ini .= PHP_EOL;
}

echo $ini;
