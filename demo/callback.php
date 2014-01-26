<?php

require __DIR__ . '/../CSVFile.php';

$csvfile = new CSVFile(__DIR__ . '/example.csv');

$total = 0;

$csvfile->read(function($item) use (&$total) {
	$total += $item['count'];
});

print "$total\n";