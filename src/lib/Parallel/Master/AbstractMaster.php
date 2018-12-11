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
abstract class AbstractMaster
{

    const MAX_SLAVES = 8;

    private $slavePids = array();

    private $slaveScript;

    private $watchQueues;

    protected $adodb;

    protected $amqpChannel;

    protected function spawnSlaves()
    {
        $this->slavePids = array();
        for ($i = 0; $i < self::MAX_SLAVES; $i ++) {
            $pid = shell_exec('nohup php ' . $this->slaveScript . ' > /dev/null & echo $!');
            $this->slavePids[] = trim($pid);
        }
    }

    protected function killSlaves()
    {
        foreach ($this->slavePids as $pid) {
            exec('kill -KILL ' . $pid);
        }
    }

    public function __construct(\ADOConnection $conn, $slaveScript, array $watchQueues)
    {
        $this->adodb = $conn;
        $this->slaveScript = $slaveScript;
        $this->watchQueues = $watchQueues;

        $connection = new AMQPStreamConnection(AMQP_HOST, AMQP_PORT, AMQP_USER, AMQP_PASS);
        $this->amqpChannel = $connection->channel();
    }

    public function processJobs()
    {
        $this->initialise();

        $this->spawnSlaves();

        do {
            sleep(1);
        } while (! $this->isFinished());

        $this->amqpChannel->close();

        $this->finalise();
    }

    protected function isFinished()
    {
        foreach ($this->watchQueues as $queueName) {
            $return = $this->amqpChannel->queue_declare($queueName, false, false, false, false);

            // Messages Ready
            if ($return[1] > 0) {
                return false;
            }
        }

        return true;
    }

    abstract protected function initialise();

    abstract protected function finalise();
}
