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
chdir('src/public_html');

if (file_exists('../conf/config.test.php')) {
    require_once '../conf/config.test.php';
} elseif (isset($_ENV['DATABASE_CONFIG_TEST'])) {
    require_once $_ENV['DATABASE_CONFIG_TEST'];
} else {
    var_dump($_ENV);
    die('ERROR: Database config missing');
}

require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

// Clean database
$adodb->Execute('TRUNCATE `fasta_peptides`');
$adodb->Execute('TRUNCATE `fasta_protein2peptide`');
$adodb->Execute('TRUNCATE `fasta_proteins`');
$adodb->Execute('TRUNCATE `job_fixed_mod`');
$adodb->Execute('TRUNCATE `job_queue`');
$adodb->Execute('TRUNCATE `raw_ms1`');
$adodb->Execute('TRUNCATE `raw_ms2`');
$adodb->Execute('TRUNCATE `users`');
$adodb->Execute('TRUNCATE `workunit1`');
$adodb->Execute('TRUNCATE `workunit1_peptides`');
?>