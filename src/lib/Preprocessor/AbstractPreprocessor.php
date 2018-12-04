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
namespace pgb_liv\crowdsource\Preprocessor;

/**
 * Logic for performing generic preprocessor method
 *
 * @author Andrew Collins
 */
abstract class AbstractPreprocessor
{

    protected $adodb;

    protected $jobId;

    protected $phase;

    /**
     * Creates a new instance of a preprocessor.
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
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }

        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    protected function setState($state)
    {
        $this->adodb->Execute('UPDATE `job_queue` SET  `state` = "' . $state . '" WHERE `id` = ' . $this->jobId);
    }

    /**
     * Marks the preprocessing stage for this phase as done
     */
    protected function finalise()
    {
        $this->setState('INDEXED');
    }
}
