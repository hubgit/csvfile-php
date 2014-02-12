<?php

class CSVFile {
	/** @var array */
	public $dialect = array(
		'length' => 0,
		'delimiter' => ',',
		'enclosure' => '"',
		'escape' => '\\',
		//'double-quote' => true,
		'skip-blank-rows' => false, // use blank rows as a table separator instead?
		//'skip-initial-space' => false,
		//'skip-final-space' => false,
	);

	/** @var array */
	private $header = array();

	/** @var array */
	private $fields = array();

	/** @var integer */
	private $columns = 0;

	/** @var resource */
	private $resource;

	/**
	 * Open a file for reading ('r', default) or writing ('w')
	 *
	 * @param string  $file
	 * @param array   $description
	 */
	public function __construct($file, $description = array()) {
		$this->resource = fopen($file, 'r');

		if (!$this->resource) {
			throw new \Exception('Unable to open file ' . $file);
		}

		print_r($description);

		$this->dialect = array_merge($this->dialect, (array) $description->dialect);
		$this->fields = $description->fields;

		if (isset($description->header)) {
			// read header row from the description
			$this->header = $description->header;
		} else {
			// read header row from the csv file
			// TODO: handle more than one header row ($description->headers->rows)
			$this->header = $this->row();
		}

		$this->columns = count($this->header);
	}

	/**
	 * Read each row of the file, calling a callback for each row
	 *
	 * @param callable $callback
	 */
	public function read($callback) {
		do {
			$row = $this->row();

			// end of the file
			if ($row === false) {
				break;
			}

			// blank row
			if ($this->dialect['skip-blank-rows'] && count($row) === 1 && is_null($row[0])) {
				continue;
			}

			// map each column to an associative array, if a header row was present
			if ($this->columns) {
				// make sure enough columns are present
				$row = array_pad($row, $this->columns, null);

				// combine column headers and row values
				$row = array_combine($this->header, $row);

				// convert values to appropriate data types
				array_walk($row, array($this, 'convert'));
			}

			call_user_func($callback, $row);
		} while (true);
	}

	/**
	 * Read a single row of the file
	 */
	public function row() {
		return fgetcsv($this->resource,
			$this->dialect['length'],
			$this->dialect['delimiter'],
			$this->dialect['enclosure'],
			$this->dialect['escape']);
	}

	/**
	 * Close the file
	 */
	public function close() {
		fclose($this->resource);
	}

	/**
	 * Convert values to appropriate data types
	 *
	 * http://www.w3.org/TR/rif-dtb/
	 */
	protected function convert(&$value, $field) {
		switch ($this->fields->{$field}->{'@type'}) {
			case 'integer':
			case 'http://www.w3.org/2001/XMLSchema#integer':
				$value = (integer) $value;
				break;

			case 'float':
			case 'http://www.w3.org/2001/XMLSchema#float':
				$value = (float) $value;
				break;

			case 'bool':
			case 'boolean':
			case 'http://www.w3.org/2001/XMLSchema#boolean':
				$value = (boolean) $value;
				break;

			case 'datetime':
			case 'dateTime':
			case 'http://www.w3.org/2001/XMLSchema#dateTime':
				// TODO: parse with specified format?
				// TODO: catch non-standard dates?
				$value = new DateTime($value);
				break;

			case 'string':
			case 'date':
			case '@id':
			default:
				break;
		}
	}
}