<?php
    $values = ['red' => 2, 'corroded' => 3, 'shiny' => 5, 'concave' => 7, 'blue' => 9];
    $v = $values;
    do {
        shuffle($v);
        $total = $v[0] + $v[1] * pow($v[2], 2) + pow($v[3], 3) - $v[4];
    } while ($total != 399);
    
    foreach ($v as $value) {
        echo array_search($value, $values), ' ';
    }
?>