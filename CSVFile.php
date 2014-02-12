<?php

class CSVFile {
	/** 
	 * CSV parser options
	 * @var array 
	 */
	public $dialect = array(
		'length' => 0, // maximum length of each line (0 = unlimited)
		'delimiter' => ',', // character that separates each cell in a row
		'enclosure' => '"', // character that (optionally) encloses each cell
		'escape' => '\\', // character that escapes the enclosure character when not enclosing
		'initial' => 0, // number of initial characters to ignore in each cell
	);

	/** 
	 * Contents of the cells in the header row
	 * @var array 
	 */
	private $header = array();

	/** 
	 * Schema for the cells in each column
	 * @var array 
	 */
	private $fields = array();

	/** 
	 * Count of the cells in the header row
	 * @var integer 
	 */
	private $columns = 0;

	/** 
	 * The CSV file
	 * @var resource 
	 */
	private $resource;

	/**
	 * Open a CSV file for reading
	 *
	 * @param string  	$file		  CSV file path
	 * @param string|null   $descriptionFile  JSON description file path
	 */
	public function __construct($file, $descriptionFile = null) {
		$this->resource = fopen($file, 'r');

		if (!$this->resource) {
			throw new \Exception('Unable to open file ' . $file);
		}
		
		if ($descriptionFile) {
			// read in the description file
			$description = json_decode(file_get_contents($descriptionFile), true);
	
			// read in the context file
			if ($description['context']) {
				$context = json_decode(file_get_contents($description['context']), true);
				$description['fields'] = $context['@context'];
			}
	
			print_r($description);

			$this->dialect = array_merge($this->dialect, $description['dialect']);

			foreach ($description['fields'] as $field => $definition) {
				$this->fields[$field] = is_string($definition) ? array('@type' => $definition) : $definition;
			}

			// skip rows
			if ($description['skip']['rows']) {
				foreach (range(1, $description['skip']['rows']) as $skip) {
					$this->row();
				}
			}

			// TODO: skip columns ($description->skip->columns)
	
		}
		
		// read header row from the csv file, or the description file
		// TODO: handle more than one header row ($description->headers->rows)
		if ($description && isset($description['header'])) {
			// read header row from the description
			$this->header = $description['header'];
		} else {
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
			if (count($row) === 1 && is_null($row[0])) {
				continue;
			}

			// comment row
			//if (substr($row[0], 0, 1) == '#') {
			//	continue;
			//}

			// map each column to an associative array, if a header row was present
			if ($this->columns) {
				// make sure enough columns are present
				$row = array_pad($row, $this->columns, null);

				// combine column headers and row values
				$row = array_combine($this->header, $row);

				// convert values to appropriate data types
				array_walk($row, array($this, 'convert'));

				// convert field names
				$row = $this->convertFields($row);
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
	 * Convert field names
	 */
	protected function convertFields($row) {
		$item = array();

		foreach ($row as $field => $value) {
			$definition = $this->fields[$field];

			if (isset($definition['@id'])) {
				$field = $definition['@id'];
			}

			$item[$field] = $value;
		}

		return $item;
	}

	/**
	 * Convert values to appropriate data types
	 *
	 * http://www.w3.org/TR/rif-dtb/
	 */
	protected function convert(&$value, $field) {
		// remove initial space(s) if required
		if ($this->dialect['initial']) {
			$value = substr($value, $this->dialect['initial']);
		}

		switch ($this->fields[$field]['@type']) {
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
				$value = $value->format(DATE_ATOM);
				break;

			case 'string':
			case 'date':
			case '@id':
			default:
				break;
		}
	}
}
