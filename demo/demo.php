<?php

require __DIR__ . '/../CSVFile.php';

// read each row of the file and process it in a callback
$items = array();

$csvfile = new CSVFile('demo.csv', 'description.json');
$csvfile->read(function($item) use (&$items) {
	$items[] = $item;
});

// output the collected data
print json_encode($items, JSON_PRETTY_PRINT);
