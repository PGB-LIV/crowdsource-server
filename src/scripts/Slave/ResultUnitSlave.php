<?php
/**
 * Copyright 2018 University of Liverpool
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
use pgb_liv\crowdsource\Parallel\Slave\ResultUnitSlave;

error_reporting(E_ALL);
ini_set('display_errors', true);

chdir(__DIR__);

require_once '../../conf/config.php';
require_once '../../conf/autoload.php';
require_once '../../conf/adodb.php';
require_once '../../vendor/autoload.php';

$slave = new ResultUnitSlave($adodb);
$slave->processQueue();
