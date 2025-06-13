<?php

/**
 * MySQL database dump loader.
 *
 * @author     Bearsthemes
 * @version    1.0
 */
class BBACKUP_MySQLImport
{
	/** @var callable  function (int $count, ?float $percent): void */
	public $onProgress;

	/** @var wpdb */
	private $connection;


	/**
	 * Connects to database.
	 * @param  wpdb connection
	 */
	public function __construct($connection, $charset = 'utf8')
	{
		$this->connection = $connection;
	}


	/**
	 * Loads dump from the file.
	 * @param  string filename
	 * @return int
	 */
	public function load($file)
	{
		$handle = strcasecmp(substr($file, -3), '.gz') ? fopen($file, 'rb') : gzopen($file, 'rb');
		if (!$handle) { throw new Exception("ERROR: Cannot open file '$file'."); }
		
		return $this->read($handle);
	}


	/**
	 * Reads dump from logical file.
	 * @param  resource
	 * @return int
	 */
	public function read($handle)
	{
		if (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
			throw new Exception('Argument must be stream resource.');
		}

		$stat = fstat($handle);

		$sql = '';
		$delimiter = ';';
		$count = $size = 0;

		while (!feof($handle)) {
			$s = fgets($handle);
			$size += strlen($s);

			if (strtoupper(substr($s, 0, 10)) === 'DELIMITER ') {
				$delimiter = trim(substr($s, 10));

			} elseif (substr($ts = rtrim($s), -strlen($delimiter)) === $delimiter) {
				$sql .= substr($ts, 0, -strlen($delimiter));
       
				if (!$this->connection->query($sql)) {
          			// wp_send_json( $sql );
					// throw new Exception($this->connection->error);
				}
				$sql = '';
				$count++;

				if ($this->onProgress) {
					call_user_func($this->onProgress, $count, isset($stat['size']) ? $size * 1000 / $stat['size'] : null);
				}
			} else {
				$sql .= $s;
			}
		}

		if (rtrim($sql) !== '') {
			$count++;
			if (!$this->connection->query($sql)) {
				// throw new Exception($this->connection->error);
			}
			if ($this->onProgress) {
				call_user_func($this->onProgress, $count, isset($stat['size']) ? 100 : null);
			}
		}

		return $count;
	}
}
