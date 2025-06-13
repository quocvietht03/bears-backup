<?php
/**
 * MySQL database dump.
 *
 * @author     Bearsthemes
 * @version    1.0
 */
class BBACKUP_MySQLDump
{
	const MAX_SQL_SIZE = 1e6;

	const NONE = 0;
	const DROP = 1;
	const CREATE = 2;
	const DATA = 4;
	const TRIGGERS = 8;
	const ALL = 15; // DROP | CREATE | DATA | TRIGGERS

	/** @var array */
	public $tables = array(
		'*' => self::ALL,
	);

	public $table_exclude = array();

	/** @var wpdb */
	private $connection;


	/**
	 * Connects to database.
	 * @param  wpbd connection
	 */
	public function __construct($connection, $charset = 'utf8')
	{
		$this->connection = $connection;
	}


	/**
	 * Saves dump to the file.
	 * @param  string filename
	 * @return void
	 */
	public function save($file)
	{
		$handle = strcasecmp(substr($file, -3), '.gz') ? fopen($file, 'wb') : gzopen($file, 'wb');
		if (!$handle) {
			throw new Exception("ERROR: Cannot write file '$file'.");
		}
		$this->write($handle);
	}

	/**
	 * @since 1.0.0
	 */
	public function replace_prefix($item) {
		return str_replace( $this->db_prefix, $this->db_prefix_replace,  $item);
	}


	/**
	 * Writes dump to logical file.
	 * @param  resource
	 * @return void
	 */
	public function write($handle = null)
	{
		if ($handle === null) {
			$handle = fopen('php://output', 'wb');
		} elseif (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
			throw new Exception('Argument must be stream resource.');
		}

		$tables = $views = array();

		$res = $this->connection->get_results('SHOW FULL TABLES');

    foreach( $res as $row ) {
      list($db__name, $db__type) = array_values( (array) $row);

      if( 'VIEW' === $db__type ) {
        $views[] = $db__name;
      } else {
        $tables[] = $db__name;
      }
    }

		// exclude table
		if( count($this->table_exclude) > 0 ) {
			$tables = array_diff( $tables, $this->table_exclude );
		}

		$tables = array_merge($tables, $views); // views must be last
		$this->connection->query('LOCK TABLES `' . implode('` READ, `', $tables) . '` READ');
		$db = $this->connection->get_results('SELECT DATABASE()');
    $__dbdata = array_values((array)$db[0])[0];

		fwrite($handle, '-- Created at ' . date('j.n.Y G:i') . " using David Grudl MySQL Dump Utility\n"
			. (isset($_SERVER['HTTP_HOST']) ? "-- Host: $_SERVER[HTTP_HOST]\n" : '')
			. '-- MySQL Server: ' . $this->connection->db_version() . "\n"
			. '-- Database: ' . $__dbdata . "\n"
			. "\n"
			. "SET NAMES utf8;\n"
			. "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n"
			. "SET FOREIGN_KEY_CHECKS=0;\n"
		);
		
		foreach ($tables as $table) {
			$this->dumpTable($handle, $table);
		}

		fwrite($handle, "-- THE END\n");
		$this->connection->query('UNLOCK TABLES');
	}


	/**
	 * Dumps table to logical file.
	 * @param  resource
	 * @return void
	 */
	public function dumpTable($handle, $table)
	{
		$delTable = $this->delimite($table);

		$row = $this->connection->get_results("SHOW CREATE TABLE $delTable", ARRAY_A);
		fwrite($handle, "-- --------------------------------------------------------\n\n");

		$mode = isset($this->tables[$table]) ? $this->tables[$table] : $this->tables['*'];
		$view = isset($row['Create View']);

		if ($mode & self::DROP) {
			fwrite($handle, 'DROP ' . ($view ? 'VIEW' : 'TABLE') . " IF EXISTS $delTable;\n\n");
		}
		
		if ($mode & self::CREATE) {
			fwrite($handle, $row[0][$view ? 'Create View' : 'Create Table'] . ";\n\n");
		}

		if (!$view && ($mode & self::DATA)) {
			$numeric = array();
			$res = $this->connection->get_results("SHOW COLUMNS FROM $delTable");
			$cols = array();

      if(count($res) > 0) {
        foreach( $res as $row ) {
          $row = (array) $row;
          $col = $row['Field'];
  				$cols[] = $this->delimite($col);
  				$numeric[$col] = (bool) preg_match('#^[^(]*(BYTE|COUNTER|SERIAL|INT|LONG$|CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER)#i', $row['Type']);
        }
      }

			$cols = '(' . implode(', ', $cols) . ')';

			$size = 0;
			$res = $this->connection->get_results("SELECT * FROM $delTable", ARRAY_A);
      if(count($res) > 0) {
        foreach($res as $row) {
          $s = '(';
  				foreach ($row as $key => $value) {

  					if ($value === null) {
  						$s .= "NULL,\t";
  					} elseif ($numeric[$key]) {
  						$s .= $value . ",\t";
  					} else {
							// add Placeholder Escape
							$value = BBACKUP_Add_Placeholder_Escape($value);
							
							$s .= "'" . $this->connection->_real_escape($value) . "',\t";
							// $s .= $value . ",\t"; // issue
  					}
					}
					
					// $s = str_replace( '{55d01eb2f04b102ff76b58585c87eac8a497720792126176ade4f8a3eb501188}', '%', $s );

  				if ($size == 0) {
  					$s = "INSERT INTO $delTable $cols VALUES\n$s";
  				} else {
  					$s = ",\n$s";
  				}

  				$len = strlen($s) - 1;
  				$s[$len - 1] = ')';
  				fwrite($handle, $s, $len);

  				$size += $len;
  				if ($size > self::MAX_SQL_SIZE) {
  					fwrite($handle, ";\n");
  					$size = 0;
  				}
        }
      }

			if ($size) {
				fwrite($handle, ";\n");
			}
			fwrite($handle, "\n");
		}

		if ($mode & self::TRIGGERS) {
			$res = $this->connection->get_results("SHOW TRIGGERS LIKE '" . $this->connection->_real_escape($table) . "'", ARRAY_A);
     
      if ($res->num_rows) {
				fwrite($handle, "DELIMITER ;;\n\n");
				while ($row = $res->fetch_assoc()) {
					fwrite($handle, "CREATE TRIGGER {$this->delimite($row['Trigger'])} $row[Timing] $row[Event] ON $delTable FOR EACH ROW\n$row[Statement];;\n\n");
				}
				fwrite($handle, "DELIMITER ;\n\n");
			}
			
		}

		fwrite($handle, "\n");
	}


	private function delimite($s)
	{
		return '`' . str_replace('`', '``', $s) . '`';
	}
}
