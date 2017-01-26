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

/**
 * Logic for performing all phase 3 preprocessing
 *
 * @author acollins
 *        
 */
class Phase3Preprocessor
{

    private $adodb;

    private $jobId;

    /**
     * Creates a new instance of the Phase 3 preprocessor.
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
        $this->adodb->Execute('UPDATE `job_queue` SET `state` = \'PREPARING\', `phase` = \'3\' WHERE `id` = ' . $this->jobId);
    }

    /**
     * Marks the preprocessing stage for this phase as done
     */
    private function finalise()
    {
        $this->adodb->Execute('UPDATE `job_queue` SET  `state` = \'READY\' WHERE `id` = ' . $this->jobId);
    }

    /**
     * Starts the indexing all phase 3 data
     */
    public function process()
    {
        $this->initialise();
        
        echo 'Pre-processing work units.' . PHP_EOL;
        $this->indexWorkUnits();
        
        $this->finalise();
    }

    /**
     * Creates an index of all phase 3 work units
     */
    private function indexWorkUnits()
    {
        // TODO: TBA
    }
}