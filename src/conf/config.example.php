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
define('DB_DRIVER', 'mysqli');
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_DB', '');

define('UNIMOD_PREFIX', 'unimod_');
define('SCHEMA_URL', 'http://www.unimod.org/xmlns/schema/unimod_tables_1/unimod_tables_1.xsd');
define('DATA_URL', 'http://www.unimod.org/xml/unimod_tables.xml');

define('DATA_PATH', '/mnt/nas/crowdsourcing/');

define('ADODB_ERROR_LOG_TYPE', 3);
define('ADODB_ERROR_LOG_DEST', '../log/adodb.log');

define('MS2_PEAK_LIMIT', 100);
define('MS2_PEAK_WINDOW', 100);

define ('MESSAGE_QUEUE', ftok(__FILE__, 'CrowdSourcing'));

/**
 * Maximum total number of modifications per peptide.
 *
 * @var int
 */
define('MAX_MOD_TOTAL', 6);

/**
 * Maximum number of different types of modifications per peptide.
 * Note MAX_MOD_TYPE * MAX_MOD_PER_TYPE does not override MAX_MOD_TOTAL.
 *
 * @var int
 */
define('MAX_MOD_TYPES', 2);

/**
 * Maximum number of occurence of single modification.
 *
 * @var int
 */
define('MAX_MOD_PER_TYPE', 4);
