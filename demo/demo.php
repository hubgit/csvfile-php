<?php

require __DIR__ . '/../CSVFile.php';

$description = json_decode(file_get_contents('description.json'));

// header inside file

$csvfile = new CSVFile($description->url, $description);

$items = array();

$csvfile->read(function($item) use (&$items) {
	$items[$item['id']] = $item;
});

print_r($items);
