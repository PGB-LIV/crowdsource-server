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
namespace pgb_liv\crowdsource\Allocator;

use pgb_liv\crowdsource\Core\WorkUnit;

class WorkUnitAllocator
{

    private $adodb;

    private $jobId;

    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException('Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    /**
     * Records an array of results to the database
     *
     * @param array $results
     *            An array of WorkUnit's with scores
     */
    public function recordResults(WorkUnit $results)
    {
        foreach ($results as $result) {
            $this->recordPeptideScores($result);
        }
        
        // Mark work unit as complete
        $rs = $adodb->Execute('UPDATE `workunit` SET `status` = \'COMPLETE\', `completed_at` = NOW() WHERE `id` = ' . $myResult->workunit);
    }

    private function recordPeptideScores($jobId, $workUnitId, array $peptide)
    {
        // only place the score if > 0
        if ($peptide->score <= 0) {
            return;
        }
        
        $this->adodb->Execute(
            'UPDATE `workunit_peptides` SET `score` = ' . $peptide->score . ' WHERE `job` = ' . $jobId . ' && `workunit` = ' . $workUnitId . ' && `peptide` = ' .
                 $peptide->id);
    }

    public function getWorkUnit()
    {
        $_myWorkUnit = new WorkUnit();
        
        // TODO: This probably should only request ready state and amend accordingly
        $job = $adodb->GetOne('SELECT `id`, `phase` FROM `job_queue` WHERE `state` = \'READY\'');
        if (is_null($jobId)) {
            // No work available
            $_myWorkUnit->job = 0; // flag no work units available;
            return $_myWorkUnit;
        }
        
        $allocator = null;
        switch ($job['phase']) {
            case '1':
                $allocator = new Phase1Allocator($this->adodb, $this->jobId);
                break;
            case '2':
                $allocator = new Phase2Allocator($this->adodb, $this->jobId);
                break;
            case '3':
                $allocator = new Phase3Allocator($this->adodb, $this->jobId);
                break;
            default:
                throw new Exception("Argh bad things happened");
        }
        
        $workUnit = $allocator->getWorkUnit();
        
        $allocator->setWorkUnitWorker(ip2long($_SERVER['REMOTE_ADDR']));
        
        return $workUnit;
    }

    /**
     * Get the peptides array from workunit_peptides
     *
     * @param unknown $wu            
     * @param unknown $job            
     */
    function getPeptides($workUnitId)
    {
        // TODO: $workUnitId
        $_peps = array();
        $rs = $adodb->Execute(
            'SELECT fpeps.id, fpeps.peptide FROM fasta_peptides AS fpeps
        LEFT OUTER JOIN workunit_peptides AS wu_p ON wu_p.peptide=fpeps.id
        WHERE wu_p.job = ' . $this->jobId . ' && wu_p.workunit=' . $workUnitId);
        $i = 0;
        while (! $rs->EOF) {
            $_peps[$i]['id'] = (int) $rs->fields['id'];
            $_peps[$i]['structure'] = $rs->fields['peptide'];
            $rs->MoveNext();
            $i ++;
        }
        
        return $_peps;
    }
}