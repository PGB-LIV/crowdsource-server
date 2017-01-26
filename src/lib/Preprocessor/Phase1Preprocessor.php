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
namespace pgb_liv\crowdsource\Preprocessor;

use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Reader\MgfReader;

/**
 * Logic for performing all phase 1 preprocessing
 *
 * @author acollins
 *        
 */
class Phase1Preprocessor
{
    // TODO: Move to config
    const MS2_PEAK_LIMIT = 50;

    private $adodb;

    private $jobId;

    private $databasePath;

    private $rawPath;

    /**
     * Creates a new instance of the Phase 1 preprocessor.
     *
     * @param \ADOConnection $conn
     *            A valid and connected ADOdb instance
     * @param int $jobId
     *            The job to preprocess
     * @throws \InvalidArgumentException If job is not an integer
     */
    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException('Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    /**
     * Marks the preprocessing stage this phase as preparing
     */
    private function initialise()
    {
        $this->adodb->Execute('UPDATE `job_queue` SET `state` = \'PREPARING\', `phase` = \'1\' WHERE `id` = ' . $this->jobId);
        
        $job = $this->adodb->GetRow('SELECT `database_file`, `raw_file` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        
        $this->databasePath = $job['database_file'];
        $this->rawPath = $job['raw_file'];
    }

    /**
     * Marks the preprocessing stage for this phase as done
     */
    private function finalise()
    {
        $this->adodb->Execute('UPDATE `job_queue` SET  `state` = \'READY\' WHERE `id` = ' . $this->jobId);
    }

    /**
     * Starts the indexing all phase 1 data
     */
    public function process()
    {
        $this->initialise();
        
        echo 'Pre-processing database: ' . $this->databasePath . PHP_EOL;
        $this->indexDatabase();
        
        echo 'Pre-processing raw data: ' . $this->rawPath . PHP_EOL;
        $this->indexRaw();
        
        echo 'Pre-processing work units.' . PHP_EOL;
        $this->indexWorkUnits();
        
        $this->finalise();
    }

    /**
     * Creates an index of all protein database file data
     */
    private function indexDatabase()
    {
        $fastaParser = new FastaReader($this->databasePath);
        
        $databaseProcessor = new DatabasePreprocessor($this->adodb, $fastaParser, $this->jobId);
        $databaseProcessor->process();
    }

    /**
     * Creates an index of all raw file data
     */
    private function indexRaw()
    {
        $mgfParser = new MgfReader($this->rawPath);
        
        $rawProcessor = new RawPreprocessor($this->adodb, $mgfParser, $this->jobId);
        $rawProcessor->setMs2PeakCount(Phase1Preprocessor::MS2_PEAK_LIMIT);
        $rawProcessor->process();
    }

    /**
     * Creates an index of all phase 1 work units
     */
    private function indexWorkUnits()
    {
        $workProcessor = new WorkUnitPreprocessor($this->adodb, $this->jobId);
        $workProcessor->process();
    }
}