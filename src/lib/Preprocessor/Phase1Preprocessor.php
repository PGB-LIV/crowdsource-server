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
 * @author Andrew Collins
 *        
 */
class Phase1Preprocessor extends AbstractPreprocessor
{

    private $databasePath;

    private $rawPath;

    /**
     * Marks the preprocessing stage this phase as preparing
     */
    protected function initialise($phase)
    {
        parent::initialise($phase);
        
        $job = $this->adodb->GetRow('SELECT `database_file`, `raw_file` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        
        $this->databasePath = $job['database_file'];
        $this->rawPath = $job['raw_file'];
    }

    /**
     * Starts the indexing all phase 1 data
     */
    public function process()
    {
        $this->initialise(1);
        
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
        $rawProcessor->setMs2PeakCount(MS2_PEAK_LIMIT);
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
