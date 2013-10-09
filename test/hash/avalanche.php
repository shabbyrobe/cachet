<?php
require __DIR__.'/../config.php';

function colour($text, $color)
{
    echo "\x1b[38;5;${color}m".$text."\033[0m";
}

if (!isset($argv[1]))
    die("Please pass hash\n");

if (!is_callable($argv[1]))
    $hash = ['Cachet\Util\Hash', $argv[1]];
else
    $hash = $argv[1];

$totals = [];

$limit = isset($argv[2]) ? $argv[2] : 1000;
for ($i = 0; $i < $limit; $i++) {
    $input = rand();
    $output = $hash($input);

    for ($j=0; $j<32; $j++) {
        $flip = $input ^ (1 << $j); 
        $flipOutput = $hash($flip);

        $diff = $output ^ $flipOutput;

        foreach (str_split(sprintf("%032s", decbin($diff))) as $idx=>$val) {
            if (!isset($totals[$j][$idx]))
                $totals[$j][$idx] = 0;

            if ($val == '1') {
                $totals[$j][$idx]++;
            }
        }
    }
}

echo "     ";
for ($i=0; $i<32; $i++) {
    echo sprintf("%02d  ", $i);
}
echo "\n    ";

for ($i=0; $i<32; $i++) {
    echo "--- ";
}
echo "\n";

$cols = [
    // 46, 46, 46, 190, 184, 178, 172, 166, 160
    46, 46, 46, 190, 172, 166, 160
];

for ($i=0; $i<32; $i++) {
    echo sprintf("%02d | ", $i);
    for ($j=0; $j<32; $j++) {
        $pct = isset($totals[$i][$j]) ? round($totals[$i][$j] / $limit * 100) : 0;
        $pad = sprintf("%-3s ", $pct);
        $col = abs($pct - 50);
        colour($pad, isset($cols[$col]) ? $cols[$col] : 160);
    }
    echo "\n";
}

