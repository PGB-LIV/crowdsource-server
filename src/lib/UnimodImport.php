<?php
class UnimodImport
{	
	public function FetchRemoteData($url)
	{
		$data = file_get_contents($url);
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'unimod_import_');

		file_put_contents($tmpFilePath, $data);

		return $tmpFilePath;
	}

	/*
	Returns an array of SQL statements to execute that will delete the old tables, then create new ones
	*/
	public function GetSqlSchema($file)
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
			$indexType = $index->getName();
			if ($indexType == 'key')
			{
				$tmp = $index->selector->attributes()['xpath'];
				$table = substr($tmp, 4, strpos($tmp, '/')-4);
				$field = substr($index->field->attributes()['xpath'], 1);

				$index = 'ALTER TABLE `'.$table.'` ADD PRIMARY KEY(`'.$field.'`);';
				$createTables[] = $index;
			}
			elseif ($indexType == 'unique')
			{
				$tmp = $index->selector->attributes()['xpath'];
				$table = substr($tmp, 4, strpos($tmp, '/')-4);
				$field = substr($index->field->attributes()['xpath'], 1);

				$index = 'ALTER TABLE `'.$table.'` ADD UNIQUE(`'.$field.'`);';
				$createTables[] = $index;
			}
		}
		
		return $createTables;
	}

	public function GetSqlData($file, $mysqli)
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
}