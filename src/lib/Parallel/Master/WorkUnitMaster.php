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

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author Andrew Collins
 */
class WorkUnitMaster extends AbstractMaster
{

    const JOB_QUEUE_NAME = 'JobQueue';

    const SLAVE_PATH = 'Slave/WorkUnitSlave.php';

    private $jobId;

    public function __construct(\ADOConnection $conn, $jobId)
    {
        parent::__construct($conn, self::SLAVE_PATH, array(
            self::JOB_QUEUE_NAME
        ));

        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }

        $this->jobId = $jobId;
    }

    private function fillQueue()
    {
        $this->amqpChannel->queue_declare(self::JOB_QUEUE_NAME, false, false, false, false);

        $recordSet = $this->adodb->Execute('SELECT `id`, `mass` FROM `raw_ms1` WHERE `job` = ' . $this->jobId);

        foreach ($recordSet as $record) {
            $object = array(
                'job' => $this->jobId,
                'precursor' => $record['id'],
                'mass' => $record['mass']
            );

            $package = json_encode($object);

            $msg = new AMQPMessage($package);

            $this->amqpChannel->basic_publish($msg, '', self::JOB_QUEUE_NAME);

            echo 'Added ' . $record['id'] . PHP_EOL;
        }
    }

    protected function initialise()
    {
        $this->adodb->Execute('UPDATE `job_queue` SET  `state` = "WORKUNITS" WHERE `id` = ' . $this->jobId);

        $this->fillQueue();
    }

    protected function finalise()
    {
        $this->adodb->Execute('UPDATE `job_queue` SET  `state` = "PROCESSING" WHERE `id` = ' . $this->jobId);
    }
}
