<?php

require __DIR__ . '/../CSVFile.php';

$description = json_decode(file_get_contents('description.json'));

$context = json_decode(file_get_contents($description->context));
$description->fields = $context->{'@context'};

// header inside file

$csvfile = new CSVFile($description->url, $description);

$items = array();

$csvfile->read(function($item) use (&$items) {
	//$items[$item['id']] = $item;
	$items[] = $item;
});

print json_encode($items, JSON_PRETTY_PRINT);
