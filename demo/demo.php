<?php

require __DIR__ . '/../CSVFile.php';

// read in the description file
$description = json_decode(file_get_contents('description.json'));

// read in the context file
if ($description->context) {
	$context = json_decode(file_get_contents($description->context));
	$description->fields = $context->{'@context'};
}

$items = array();

$csvfile = new CSVFile($description->url, $description);
$csvfile->read(function($item) use (&$items) {
	$items[] = $item;
});

print json_encode($items, JSON_PRETTY_PRINT);
