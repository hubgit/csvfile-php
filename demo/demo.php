<?php

require __DIR__ . '/../CSVFile.php';

$description = json_decode(file_get_contents('description.json'));

// header inside file

$csvfile = new CSVFile(__DIR__ . '/header.csv', $description);

$items = array();

$csvfile->read(function($item) use (&$items) {
	$items[$item['id']] = $item;
});

print_r($items);

// header outside file

$description->header = ["id", "title", "volume", "date published"];

$csvfile = new CSVFile(__DIR__ . '/no-header.csv', $description);

$items = array();

$csvfile->read(function($item) use (&$items) {
	$items[$item['id']] = $item;
});

print_r($items);