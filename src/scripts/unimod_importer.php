<?php
/**
 * Copyright 2016 University of Liverpool
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/*
 * Initialises the unimod database with data from Unimod, then extends it to add additional mass data
 */
include '../conf/config.php';
include '../lib/UnimodImport.php';

/*
 * Masses source: http://www.matrixscience.com/help/aa_help.html
 */
$_AMINO_MASS = array();
$_AMINO_MASS['A'] = array(
    'mono' => 71.037114,
    'avg' => 71.0779
);
$_AMINO_MASS['R'] = array(
    'mono' => 156.101111,
    'avg' => 156.1857
);
$_AMINO_MASS['N'] = array(
    'mono' => 114.042927,
    'avg' => 114.1026
);
$_AMINO_MASS['D'] = array(
    'mono' => 115.026943,
    'avg' => 115.0874
);
$_AMINO_MASS['C'] = array(
    'mono' => 103.009185,
    'avg' => 103.1429
);
$_AMINO_MASS['E'] = array(
    'mono' => 129.042593,
    'avg' => 129.114
);
$_AMINO_MASS['Q'] = array(
    'mono' => 128.058578,
    'avg' => 128.1292
);
$_AMINO_MASS['G'] = array(
    'mono' => 57.021464,
    'avg' => 57.0513
);
$_AMINO_MASS['H'] = array(
    'mono' => 137.058912,
    'avg' => 137.1393
);
$_AMINO_MASS['I'] = array(
    'mono' => 113.084064,
    'avg' => 113.1576
);
$_AMINO_MASS['L'] = array(
    'mono' => 113.084064,
    'avg' => 113.1576
);
$_AMINO_MASS['K'] = array(
    'mono' => 128.094963,
    'avg' => 128.1723
);
$_AMINO_MASS['M'] = array(
    'mono' => 131.040485,
    'avg' => 131.1961
);
$_AMINO_MASS['F'] = array(
    'mono' => 147.068414,
    'avg' => 147.1739
);
$_AMINO_MASS['P'] = array(
    'mono' => 97.052764,
    'avg' => 97.1152
);
$_AMINO_MASS['S'] = array(
    'mono' => 87.032028,
    'avg' => 87.0773
);
$_AMINO_MASS['T'] = array(
    'mono' => 101.047679,
    'avg' => 101.1039
);
$_AMINO_MASS['U'] = array(
    'mono' => 150.95363,
    'avg' => 150.0379
);
$_AMINO_MASS['W'] = array(
    'mono' => 186.079313,
    'avg' => 186.2099
);
$_AMINO_MASS['Y'] = array(
    'mono' => 163.06332,
    'avg' => 163.1733
);
$_AMINO_MASS['V'] = array(
    'mono' => 99.068414,
    'avg' => 99.1311
);

echo 'Connected to MySQL... ';
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
if ($mysqli->connect_errno) {
    die('Failed to connect to MySQL: ' . $mysqli->connect_error);
}

echo 'Connected' . "\n\n";

$unimod = new UnimodImport();
echo 'Fetching schema... ';
$schemaPath = $unimod->fetchRemoteData(SCHEMA_URL);
echo 'Done' . "\n" . 'Saved to ' . $schemaPath . "\n\n";

echo 'Parsing schema... ';
$schema = $unimod->getSqlSchema($schemaPath);
echo 'Done' . "\n\n";

echo 'Inserting schema... ';
foreach ($schema as $query) {
    $res = $mysqli->query($query);
    if (! $res) {
        die($mysqli->errno . ') ' . $mysqli->error . "\n" . $query);
    }
}

echo 'Done' . "\n\n";

echo 'Fetching data... ';
$dataPath = $unimod->fetchRemoteData(DATA_URL);
echo 'Done' . "\n" . 'Saved to ' . $dataPath . "\n";

echo 'Parsing schema... ';
$data = $unimod->getSqlData($dataPath, $mysqli);
echo 'Done' . "\n\n";

echo 'Inserting data... ';
foreach ($data as $tableName => $table) {
    echo "\n" . $tableName . '...';
    foreach ($table as $query) {
        $res = $mysqli->query($query);
        if (! $res) {
            die($mysqli->errno . ') ' . $mysqli->error . "\n" . $query);
        }
    }
    
    echo 'Done.';
}

echo "\n\n" . 'Unimod update complete' . "\n\n";

echo 'Adding mass values to amino acids...';
$mysqli->query('ALTER TABLE `amino_acids` ADD `avg_mass` DOUBLE NOT NULL AFTER `num_Se`, ADD `mono_mass` DOUBLE NOT NULL AFTER `avg_mass`;');
foreach ($_AMINO_MASS as $aminoAcid => $mass) {
    $res = $mysqli->query('UPDATE `amino_acids` SET `avg_mass` = ' . $mass['avg'] . ', `mono_mass` = ' . $mass['mono'] . ' WHERE `one_letter` = \'' . $aminoAcid . '\';');
    if (! $res) {
        die($mysqli->errno . ') ' . $mysqli->error . "\n" . $query);
    }
}

echo 'Done' . "\n\n";

echo 'Database Update Complete' . "\n";
