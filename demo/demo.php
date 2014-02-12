<?php

require __DIR__ . '/../CSVFile.php';

// read in the description file
$description = json_decode(file_get_contents('description.json'));

// read in the context file
if ($description->context) {
	$context = json_decode(file_get_contents($description->context));
	$description->fields = $context->{'@context'};
}

// read each row of the file and process it in a callback
$items = array();

$csvfile = new CSVFile($description->url, $description);
$csvfile->read(function($item) use (&$items) {
	$items[] = $item;
});

// output the collected data
print json_encode($items, JSON_PRETTY_PRINT);
