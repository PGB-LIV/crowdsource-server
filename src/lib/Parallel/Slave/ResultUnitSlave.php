<?php
/**
 * Copyright 2019 University of Liverpool
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
use pgb_liv\crowdsource\Core\WorkUnit;
use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\crowdsource\Parallel\Master\ResultUnitMaster;
use pgb_liv\crowdsource\BulkQuery;
use pgb_liv\php_ms\Utility\Sort\IdentificationSort;
use pgb_liv\php_ms\Core\Identification;

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author Andrew Collins
 */
class ResultUnitSlave extends AbstractSlave
{

    const RESULT_QUEUE_NAME = 'ResultQueue';

    private $bulkWorkUnit;

    private $bulkLocation;

    private $pendingAcks = array();

    private $pendingIncrement = array();

    /**
     *
     * @var IdentificationSort
     */
    private $identificationSort;

    public function __construct(\ADOConnection $conn)
    {
        parent::__construct($conn, self::RESULT_QUEUE_NAME);

        $this->bulkWorkUnit = new BulkQuery($this->adodb,
            'INSERT IGNORE INTO `workunit1` (`job`,`precursor`,`peptide`,`ions_matched`,`score`) VALUES ', false);

        $this->bulkLocation = new BulkQuery($this->adodb,
            'INSERT IGNORE INTO `workunit1_locations` (`job`,`precursor`,`peptide`,`location`,`modification`) VALUES ',
            false);

        $this->identificationSort = new IdentificationSort(IdentificationSort::SORT_SCORE, SORT_DESC);
        $this->identificationSort->setScoreKey(SCORE_PROPERTY);
    }

    private function processResult($result)
    {
        $jsonStr = gzdecode($result);

        $workUnit = WorkUnit::fromJson($jsonStr);

        // JobId, PrecursorId, PacketNum
        $uid = $workUnit->getUid();
        $uidParts = explode('-', $uid);

        $jobId = (int) $uidParts[0];
        $precursorId = (int) $uidParts[1];

        if (! isset($this->pendingIncrement[$jobId])) {
            $this->pendingIncrement[$jobId] = 0;
        }

        $this->pendingIncrement[$jobId] ++;

        $identifications = $workUnit->getIdentifications();

        $this->identificationSort->sort($identifications, false);

        // Only store top N results
        for ($identIndex = 0; $identIndex < min(count($identifications), MAX_PRECURSOR_IDENTS); $identIndex ++) {
            $identification = $identifications[$identIndex];
            if ($identification->getScore(SCORE_PROPERTY) == 0) {
                continue;
            }

            $this->recordIdentification($jobId, $precursorId, $identification);
        }

        if ($this->bulkWorkUnit->isExecRequired() || $this->bulkLocation->isExecRequired() ||
            array_sum($this->pendingIncrement) > 100) {
            $this->flush();
        }

        $this->adodb->Execute(
            'INSERT IGNORE INTO `analytic_meta` (`job`, `uid`, `sent`, `received`, `ip`, `host`, `cpu`) VALUES (' .
            $jobId . ',' . $this->adodb->Quote($workUnit->getUid()) . ',' . $workUnit->getBytesSent() . ',' .
            $workUnit->getBytesReceived() . ',' . $workUnit->getUser() . ',' .
            $this->adodb->Quote($workUnit->getHostname()) . ',' . $workUnit->getProcessTime() . ')');
    }

    private function recordIdentification($jobId, $precursorId, Identification $identification)
    {
        $peptide = $identification->getSequence();
        $peptideId = (int) $peptide->getId();
        $ionsMatched = $identification->getIonsMatched();
        $score = (float) $identification->getScore(SCORE_PROPERTY);

        $this->bulkWorkUnit->append(
            '(' . $jobId . ',' . $precursorId . ',' . $peptideId . ',' . $ionsMatched . ',' . $score . ')');

        foreach ($peptide->getModifications() as $mod) {
            $this->bulkLocation->append(
                '(' . $jobId . ',' . $precursorId . ',' . $peptideId . ',' . $mod->getLocation() . ',' . $mod->getId() .
                ')');
        }
    }

    private function flush()
    {
        $this->bulkWorkUnit->flush();
        $this->bulkLocation->flush();

        foreach ($this->pendingAcks as $ack) {
            $this->amqpChannel->basic_ack($ack);
        }

        foreach ($this->pendingIncrement as $jobId => $increment) {
            $this->adodb->Execute(
                'UPDATE `job_queue` SET `workunits_returned` = `workunits_returned` + ' . $increment . ' WHERE `id` =' .
                $jobId);
        }

        $this->pendingAcks = array();
        $this->pendingIncrement = array();
    }

    protected function finalise()
    {
        $this->flush();
    }

    public function processJob($message)
    {
        $this->processResult($message->body);

        $this->pendingAcks[] = $message->delivery_info['delivery_tag'];
    }

    protected function initialise()
    {}
}
