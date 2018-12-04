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
namespace pgb_liv\crowdsource\Parallel\Slave;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPTimeoutException;

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author Andrew Collins
 */
abstract class AbstractSlave
{

    protected $adodb;

    protected $amqpConnection;

    protected $amqpChannel;

    private $watchChannel;

    public function __construct(\ADOConnection $conn, $watchChannel)
    {
        $this->adodb = $conn;
        $this->amqpConnection = new AMQPStreamConnection(AMQP_HOST, AMQP_PORT, AMQP_USER, AMQP_PASS);
        $this->watchChannel = $watchChannel;
    }

    public function processQueue()
    {
        $this->amqpChannel = $this->amqpConnection->channel();
        $this->amqpChannel->queue_declare($this->watchChannel, false, false, false, false);

        $callback = array(
            $this,
            'processJob'
        );

        $this->amqpChannel->basic_qos(null, 1000, null);
        $this->amqpChannel->basic_consume($this->watchChannel, '', false, false, false, false, $callback);

        $this->initialise();

        while (count($this->amqpChannel->callbacks)) {
            try {
                $this->amqpChannel->wait(null, false, 30);
            } catch (AMQPTimeoutException $e) {
                // No data in stream
                echo 'Queue empty' . PHP_EOL;
                break;
            }
        }

        $this->finalise();

        $this->amqpChannel->close();
        $this->amqpConnection->close();
    }

    public abstract function processJob($message);

    protected abstract function initialise();

    protected abstract function finalise();
}