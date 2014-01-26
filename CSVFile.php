<?php

class CSVFile {
	/** @var string */
	public $delimiter = ',';

	/** @var integer */
	public $length = 0;

	/** @var string */
	public $enclosure = '"';

	/** @var string */
	public $escape = '\\';

	/** @var boolean */
	public $skipBlankRows = false;

	/** @var array */
	private $header = array();

	/** @var integer */
	private $columns = 0;

	/** @var resource */
	private $resource;

	/**
	 * Open a file for reading ('r', default) or writing ('w')
	 *
	 * @param string      $file
	 * @param string|null $mode
	 * @param bool|null   $header
	 */
	public function __construct($file, $mode = 'r', $header = true) {
		$this->resource = fopen($file, $mode);

		if (!$this->resource) {
			throw new \Exception('Unable to open file ' . $file);
		}

		if ($mode == 'r' && $header) {
			$this->header();

			// TODO: read description from JSON file?
		}
	}

	/**
	 * Read each row of the file, calling a callback for each row
	 *
	 * @param callable $callback
	 */
	public function read($callback) {
		do {
			$row = $this->row();

			if ($row === false) {
				break;
			}

			if ($this->skipBlankRows && count($row) === 1 && is_null($row[0])) {
				continue;
			}

			// call header() first to read the header row
			if ($this->columns) {
				// make sure enough columns are present
				$row = array_pad($row, $this->columns, null);

				// map each column to an associative array
				$row = array_combine($this->header, $row);
			}

			call_user_func($callback, $row);
		} while (true);
	}

	/**
	 * Read a single row of the file
	 */
	public function row() {
		return fgetcsv($this->resource, $this->length, $this->delimiter, $this->enclosure, $this->escape);
	}

	/**
	 * Read the header row
	 */
	public function header() {
		$this->header = $this->row();
		$this->columns = count($this->header);
	}

	/**
	 * Write a row to the file
	 */
	public function write($data) {
		fputcsv($this->resource, $data);
	}

	/**
	 * Close the file
	 */
	public function close() {
		fclose($this->resource);
	}
}