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
use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\crowdsource\Core\Modification;

class Phase1Allocator extends AbstractAllocator implements AllocatorInterface
{

    private $modMass = array();

    private $modRes = array();

    /**
     * Creates a new instance of the Phase 1 allocator.
     *
     * @param \ADOConnection $conn
     *            A valid and connected ADOdb instance
     * @param int $jobId
     *            The job to preprocess
     * @throws \InvalidArgumentException If job is not an integer
     */
    public function __construct(\ADOConnection $conn, $jobId)
    {
        parent::__construct($conn, $jobId);

        $this->setPhase(1);

        $this->modMass = $this->adodb->GetAssoc(
            'SELECT DISTINCT `mod_id`, `mono_mass` FROM `job_variable_mod` LEFT JOIN `unimod_modifications` ON `mod_id` = `record_id`  WHERE `job` = ' .
            $jobId);

        foreach (array_keys($this->modMass) as $key) {
            $this->modRes[$key] = $this->adodb->GetCol(
                'SELECT `acid` FROM `job_variable_mod` WHERE `job` = ' . $jobId . ' && `mod_id` = ' . $key);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \pgb_liv\crowdsource\Allocator\AllocatorInterface::getWorkUnit()
     */
    public function getWorkUnit($workerId)
    {
        $lockVal = mt_rand();
        // TODO: Reset failed allocations before running

        $queue = msg_get_queue(MESSAGE_QUEUE);
        // Pull
        $type = '';
        $precursorId = '';
        $errorCode = '';
        do {
            $hasMessage = msg_receive($queue, 0, $type, 4096, $precursorId, true, MSG_IPC_NOWAIT, $errorCode);

            if (! $hasMessage) {
                // Queue is empty
                return true;
            }
        } while ($type != $this->jobId);

        $this->adodb->Execute(
            'UPDATE `workunit1` SET `status` = \'ASSIGNED\', `assigned_to` =' . $workerId .
            ', `assigned_at` = NOW(), `allocation_lock` = ' . $lockVal .
            ' 
             WHERE `job` = ' .
            $this->jobId . ' && `precursor` = ' . $precursorId . ' && `status` != \'COMPLETE\' ORDER BY `id` LIMIT 50');

        // $workUnit = $this->getNextWorkUnit($workerId);
        $workUnit = new WorkUnit($this->jobId, (int) $precursorId);

        // if (! $workUnit) {
        // return false;
        // }
        // SELECT DATE_FORMAT(`completed_at`, '%H%i') `time`, COUNT(DISTINCT `assigned_to`) `users`, COUNT(*) `processed` FROM `workunit1` WHERE `status` =
        // 'COMPLETE' GROUP BY DATE_FORMAT(`completed_at`, '%H%i') ORDER BY `time` DESC

        $tolerance = $this->getTolerance();
        if (! $tolerance) {
            return false;
        }

        $workUnit->setFragmentTolerance($tolerance);
        $this->injectFragmentIons($workUnit);
        $this->injectPeptides($workUnit, $lockVal);
        $this->injectFixedModifications($workUnit);

        return $workUnit;
    }

    private function injectPeptides(WorkUnit $workUnit, $lockVal)
    {
        $rs = $this->adodb->Execute(
            'SELECT `w`.`id`, `p`.`peptide`, `modifications` FROM `workunit1` `w` LEFT JOIN `fasta_peptides` `p` ON `p`.`id` = `w`.`peptide` && `p`.`fasta` = ' .
            $this->fastaId . ' WHERE `job` = ' . $this->jobId . ' && `precursor`=' . $workUnit->getPrecursorId() .
            ' && `allocation_lock` = ' . $lockVal);

        foreach ($rs as $record) {
            $peptide = new Peptide((int) $record['id']);

            if (! is_null($record['modifications'])) {
                $mods = explode(':', $record['modifications']);
                foreach ($mods as $modId) {
                    $modification = new Modification((int) $modId);
                    $modification->setMonoisotopicMass((float) $this->modMass[$modId]);
                    $modification->setResidues($this->modRes[$modId]);

                    $peptide->addModification($modification);
                }
            }
            $peptide->setSequence($record['peptide']);
            $workUnit->addPeptide($peptide);
        }
    }

    protected function recordPeptideScores($precursorId, Peptide $peptide)
    {
        $this->adodb->Execute(
            'UPDATE `workunit1` SET `status` = \'COMPLETE\' WHERE `job` = ' . $this->jobId . ' && `id` = ' .
            $peptide->getId());

        if ($this->adodb->affected_rows() == 0) {
            return;
        }

        $this->adodb->Execute(
            'UPDATE `workunit1` SET `completed_at` = NOW(), `score` = ' . $peptide->getScore() . ', `ions_matched` = ' .
            $peptide->getIonsMatched() . ' WHERE `job` = ' . $this->jobId . ' && `id` = ' . $peptide->getId());

        // TODO issue here?
        foreach ($peptide->getModifications() as $modification) {
            $this->adodb->Execute(
                'INSERT INTO `workunit1_locations` (`job`, `id`, `location`, `modification`) VALUES (' . $this->jobId .
                ',' . $peptide->getId() . ',' . $modification->getLocation() . ', ' . $modification->getId() . ')');
        }
    }

    public function setWorkUnitResults(WorkUnit $workUnit)
    {
        foreach ($workUnit->getPeptides() as $peptide) {
            $this->recordPeptideScores($workUnit->getPrecursorId(), $peptide);
        }
    }
}
