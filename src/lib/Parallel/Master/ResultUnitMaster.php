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
namespace pgb_liv\crowdsource\Parallel\Master;

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author Andrew Collins
 */
class ResultUnitMaster extends AbstractMaster
{

    const JOB_QUEUE_NAME = 'JobQueue';

    const RESULT_QUEUE_NAME = 'ResultQueue';

    const SLAVE_PATH = 'Slave/ResultUnitSlave.php';

    public function __construct(\ADOConnection $conn)
    {
        parent::__construct($conn, self::SLAVE_PATH, array(
            self::JOB_QUEUE_NAME,
            self::RESULT_QUEUE_NAME
        ));
    }

    protected function initialise()
    {
        // Nothing required
    }

    protected function finalise()
    {
        // Nothing required
    }
}
