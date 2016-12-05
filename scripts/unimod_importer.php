<?php
/*
 * Initialises the unimod database with data from Unimod, then extends it to add additional mass data
 */
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', '');
define('MYSQL_PASS', '');
define('MYSQL_DB', 'unimod');

define('SCHEMA_URL', 'http://www.unimod.org/xmlns/schema/unimod_tables_1/unimod_tables_1.xsd');
define('DATA_URL', 'http://www.unimod.org/xml/unimod_tables.xml');

/*
 * Masses source: http://www.matrixscience.com/help/aa_help.html
 */
$_AMINO_MASS = array();
$_AMINO_MASS['A'] = array('mono' => 71.037114, 	'avg' => 71.0779);
$_AMINO_MASS['R'] = array('mono' => 156.101111,	'avg' => 156.1857);
$_AMINO_MASS['N'] = array('mono' => 114.042927,	'avg' => 114.1026);
$_AMINO_MASS['D'] = array('mono' => 115.026943,	'avg' => 115.0874);
$_AMINO_MASS['C'] = array('mono' => 103.009185,	'avg' => 103.1429);
$_AMINO_MASS['E'] = array('mono' => 129.042593,	'avg' => 129.114);
$_AMINO_MASS['Q'] = array('mono' => 128.058578,	'avg' => 128.1292);
$_AMINO_MASS['G'] = array('mono' => 57.021464,	'avg' => 57.0513);
$_AMINO_MASS['H'] = array('mono' => 137.058912,	'avg' => 137.1393);
$_AMINO_MASS['I'] = array('mono' => 113.084064,	'avg' => 113.1576);
$_AMINO_MASS['L'] = array('mono' => 113.084064,	'avg' => 113.1576);
$_AMINO_MASS['K'] = array('mono' => 128.094963,	'avg' => 128.1723);
$_AMINO_MASS['M'] = array('mono' => 131.040485,	'avg' => 131.1961);
$_AMINO_MASS['F'] = array('mono' => 147.068414,	'avg' => 147.1739);
$_AMINO_MASS['P'] = array('mono' => 97.052764,	'avg' => 97.1152);
$_AMINO_MASS['S'] = array('mono' => 87.032028,	'avg' => 87.0773);
$_AMINO_MASS['T'] = array('mono' => 101.047679,	'avg' => 101.1039);
$_AMINO_MASS['U'] = array('mono' => 150.95363,	'avg' => 150.0379);
$_AMINO_MASS['W'] = array('mono' => 186.079313,	'avg' => 186.2099);
$_AMINO_MASS['Y'] = array('mono' => 163.06332,	'avg' => 163.1733);
$_AMINO_MASS['V'] = array('mono' => 99.068414,	'avg' => 99.1311);

function FetchRemoteData($url)
{
	$data = file_get_contents($url);
	$tmpFilePath = tempnam(sys_get_temp_dir(), 'unimod_import_');

	file_put_contents($tmpFilePath, $data);
	
	return $tmpFilePath;
}

/*
Returns an array of SQL statements to execute that will delete the old tables, then create new ones
*/
function GetSqlSchema($file)
{
	// Parse schema
	$schema = new SimpleXMLElement($file, null, true, 'xs', true);

	$createTables = array();
	foreach ($schema->element->complexType->sequence->element as $table)
	{
		$tableName = (string) $table->attributes()->name;
		
		$createTable = 'CREATE TABLE `'.$tableName.'` (';
		foreach ($table->complexType->sequence->element->complexType->attribute as $column)
		{		
			$columnName = (string) $column->attributes()->name;
			$columnType = 'UNKNOWN';
			switch((string) $column->attributes()->type)
			{
				case 'xs:double':
					$columnType = 'DOUBLE(20,10)';
					break;
				case 'xs:byte':
					$columnType = 'TINYINT(3)';
					break;
				case 'xs:integer':
					$columnType = 'INTEGER(11)';
					break;
				case 'xs:long':
					$columnType = 'BIGINT(20)';
					break;
				case 'xs:string':
					$columnType = 'VARCHAR(255)';
					break;
				default:
					die('Unknown data type: '.(string) $column->attributes()->type);
					break;
			}
			
			$createTable .= "\n";
			$createTable .= '`'.$columnName .'` '.$columnType.',';
		}
		if ($tableName == 'amino_acids')
		{
			$createTable .= '`num_Se` INTEGER(11),';
		}
		$createTable = substr($createTable, 0, -1);
		$createTable .= "\n";
		$createTable .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8;';

		$createTables[] = 'DROP TABLE IF EXISTS `'.$tableName.'`;';
		$createTables[] = $createTable;
	}
	
	foreach ($schema->element->children('xs', true) as $index)
	{
		switch ($index->getName())
		{
			case 'key':
				$tmp = $index->selector->attributes()['xpath'];
				$table = substr($tmp, 4, strpos($tmp, '/')-4);
				$field = substr($index->field->attributes()['xpath'], 1);
				
				$index = 'ALTER TABLE `'.$table.'` ADD PRIMARY KEY(`'.$field.'`);';
				$createTables[] = $index;
			break;
			case 'unique':
				$tmp = $index->selector->attributes()['xpath'];
				$table = substr($tmp, 4, strpos($tmp, '/')-4);
				$field = substr($index->field->attributes()['xpath'], 1);
				
				$index = 'ALTER TABLE `'.$table.'` ADD UNIQUE(`'.$field.'`);';
				$createTables[] = $index;
			break;
		}
	}
	return $createTables;
}

function GetSqlData($file, $mysqli)
{
	// Parse schema
	$schema = new SimpleXMLElement($file, null, true);

	$data = array();
	foreach ($schema as $table)
	{
		$tableName = $table->getName();
		$data[$tableName] = array();
		foreach ($schema->$tableName->children() as $row)
		{
			$insert = 'INSERT IGNORE INTO `'.$tableName.'` (';
			foreach ($row->attributes() as $column => $value)
			{
				$insert .= '`'.$column . '`,';
			}
			$insert = substr($insert, 0, -1);
			
			$insert .= ') VALUES (';
			foreach ($row->attributes() as $column => $value)
			{
				$insert .= '\''.$mysqli->real_escape_string($value) . '\',';
			}
			$insert = substr($insert, 0, -1);
			$insert .= ');';
			
			$data[$tableName][] = $insert;
		}
	}
	
	return $data;
}

echo 'Connected to MySQL... ';
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
if ($mysqli->connect_errno) {
    die('Failed to connect to MySQL: ' . $mysqli->connect_error);
}
echo 'Connected'."\n\n";

echo 'Fetching schema... ';
$schemaPath = FetchRemoteData(SCHEMA_URL);
echo 'Done'."\n".'Saved to '. $schemaPath."\n\n";

echo 'Parsing schema... ';
$schema = GetSqlSchema($schemaPath);
echo 'Done'."\n\n";

echo 'Inserting schema... ';
foreach ($schema as $query)
{
	$res = $mysqli->query($query);
	if (!$res) {		
		die("Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error."\n".$query);
	}
	
}
echo 'Done'."\n\n";


echo 'Fetching data... ';
$dataPath = FetchRemoteData(DATA_URL);
echo 'Done'."\n".'Saved to '. $dataPath."\n";

echo 'Parsing schema... ';
$data = GetSqlData($dataPath, $mysqli);
echo 'Done'."\n\n";

echo 'Inserting data... ';
foreach ($data as $tableName => $table)
{
	echo "\n".$tableName.'...';
	foreach ($table as $query)
	{
		$res = $mysqli->query($query);
		if (!$res) {
			die("Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error."\n".$query);
		}
	}
	echo 'Done.';
}

echo "\n\n".'Unimod update complete'."\n\n";

echo 'Adding mass values to amino acids...';
$mysqli->query('ALTER TABLE `amino_acids` ADD `avg_mass` DOUBLE NOT NULL AFTER `num_Se`, ADD `mono_mass` DOUBLE NOT NULL AFTER `avg_mass`;');
foreach ($_AMINO_MASS as $aminoAcid => $mass)
{
	$res = $mysqli->query('UPDATE `amino_acids` SET `avg_mass` = '.$mass['avg'].', `mono_mass` = '.$mass['mono'].' WHERE `one_letter` = \''.$aminoAcid.'\';');
	if (!$res) {		
		echo("Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error."\n".$query);
	}
}
echo 'Done'."\n\n";
?>