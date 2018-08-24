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
use pgb_liv\crowdsource\Core\FragmentIon;
use pgb_liv\crowdsource\Core\Modification;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\crowdsource\Core\Peptide;

abstract class AbstractAllocator implements AllocatorInterface
{

    protected $adodb;

    protected $jobId;

    protected $fastaId;

    private $phase;

    /**
     * Creates a new instance of a job allocator.
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
        $this->fastaId = $this->adodb->GetOne(
            'SELECT `f`.`id` FROM `job_queue` `j` LEFT JOIN `fasta` `f` ON `database_hash` = `hash` && `j`.`enzyme` = `f`.`enzyme` WHERE `j`.`id` = ' .
            $this->jobId);
    }

    protected function setPhase($phase)
    {
        $this->phase = $phase;
    }

    /**
     * Checks if the current phases work units are marked as COMPLETE.
     *
     * @return boolean Returns true if all work units are marked as complete, else false.
     */
    protected function isPhaseComplete()
    {
        $incompleteCount = $this->adodb->GetOne(
            'SELECT COUNT(`job`) FROM `workunit' . $this->phase . '` WHERE `status` != \'COMPLETE\'');

        return $incompleteCount == 0;
    }

    /**
     * Marks the job as complete for this phase
     */
    protected function setJobDone()
    {
        $this->adodb->Execute(
            'UPDATE `job_queue` SET `state` = \'DONE\' WHERE `id` = ' . $this->jobId .
            ' && `state` = \'READY\' && phase = \'' . $this->phase . '\'');
    }

    protected function injectFixedModifications(WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `job_fixed_mod`.`mod_id`, `unimod_modifications`.`mono_mass`, `job_fixed_mod`.`acid` FROM `job_fixed_mod`
    INNER JOIN `unimod_modifications` ON `unimod_modifications`.`record_id` = `job_fixed_mod`.`mod_id` WHERE 
            `job_fixed_mod`.`job` = ' . $workUnit->getJobId());

        foreach ($rs as $record) {
            $modification = new Modification((int) $record['mod_id'], (float) $record['mono_mass'],
                array(
                    $record['acid']
                ));
            $workUnit->addFixedModification($modification);
        }
    }

    /**
     * Injects the MS/MS data into the work unit object
     *
     * @param WorkUnit $workUnit
     *            Work unit to inject into
     */
    protected function injectFragmentIons(WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `mz`, `intensity` FROM `raw_ms2` WHERE `job` = ' . $workUnit->getJobId() . ' && `ms1` = ' .
            $workUnit->getPrecursorId());

        foreach ($rs as $record) {
            $workUnit->addFragmentIon(new FragmentIon((float) $record['mz'], (float) $record['intensity']));
        }
    }

    protected function getTolerance()
    {
        $toleranceRaw = $this->adodb->GetRow(
            'SELECT `fragment_tolerance`, `fragment_tolerance_unit` FROM `job_queue` WHERE `id` = ' . $this->jobId);

        if (empty($toleranceRaw)) {
            return false;
        }

        return new Tolerance((float) $toleranceRaw['fragment_tolerance'], $toleranceRaw['fragment_tolerance_unit']);
    }

    protected function getNextWorkUnit($workerId)
    {
        if (! is_int($workerId)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($workerId));
        }

        $attempts = 0;
        do {
            if ($attempts >= 5) {
                return false;
            }

            // Select any jobs unassigned or assigned but not completed in the past minute
            $available = $this->adodb->GetRow(
                'SELECT `precursor`, `allocation_lock` FROM `workunit' . $this->phase . '` WHERE `job` =' . $this->jobId .
                ' && (`status` = \'UNASSIGNED\' || ( `completed_at` IS NULL && `assigned_at` < NOW() - INTERVAL 1 MINUTE)) LIMIT 0, 1');

            if (empty($available)) {
                // All work units are possibly complete
                if ($this->isPhaseComplete()) {
                    $this->setJobDone();
                }

                return false;
            }

            $lockVal = mt_rand();

            // Attempt lock
            $this->adodb->Execute(
                'UPDATE `workunit' . $this->phase . '` SET `status` = \'ASSIGNED\', `assigned_to` =' . $workerId .
                ', `assigned_at` = NOW(), `allocation_lock` = ' . $lockVal . ' WHERE `job` = ' . $this->jobId .
                ' && `precursor` = ' . $available['precursor'] . ' && `allocation_lock` = ' .
                $available['allocation_lock']);

            $attempts ++;
        } while ($this->adodb->affected_rows() !== 1);

        return new WorkUnit($this->jobId, (int) $available['precursor']);
    }

    abstract public function getWorkUnit($workerId);

    public function setWorkUnitResults(WorkUnit $workUnit)
    {
        $this->rescore($workUnit);

        foreach ($workUnit->getPeptides() as $peptide) {
            $this->recordPeptideScores($workUnit->getPrecursorId(), $peptide);
        }

        // Mark work unit as complete
        $this->adodb->Execute(
            'UPDATE `workunit' . $this->phase .
            '` SET `status` = \'COMPLETE\', `completed_at` = NOW() WHERE `precursor` = ' . $workUnit->getPrecursorId() .
            ' && `job` =' . $this->jobId);
    }

    abstract protected function recordPeptideScores($precursorId, Peptide $peptide);

    /**
     * Rescores the received peptides to boost for delta precursor masses
     *
     * @param WorkUnit $workUnit
     *            Work unit to rescore
     * @throws \OutOfBoundsException If precursor or peptide ID is not found
     * @return void
     */
    protected function rescore(WorkUnit $workUnit)
    {
        // TODO: This rescoring could be more efficient if done at a post-processing phase.
        $precursorMass = $this->adodb->GetOne(
            'SELECT `mass` FROM `raw_ms1` WHERE `job` = ' . $this->jobId . ' && `id` = ' . $workUnit->getPrecursorId());

        if (is_null($precursorMass)) {
            throw new \OutOfBoundsException('Precursor "' . $workUnit->getPrecursorId() . '" not found');
        }

        $modToMass = $this->adodb->GetAssoc('SELECT `record_id`, `mono_mass` FROM `unimod_modifications`');
        $labelledMods = $this->adodb->GetCol(
            'SELECT `mod_key`, SUM(IF(`classifications_key` = 11, 1, 0)) AS `label_sites`, COUNT(`mod_key`) AS `sites` FROM `unimod_specificity` GROUP BY `mod_key` HAVING `label_sites` = `sites`');

        foreach ($workUnit->getPeptides() as $peptide) {
            if ($peptide->getScore() <= 0) {
                continue;
            }

            if ($peptide->isModified()) {
                $this->rescoreModifications($peptide, $labelledMods);
            }

            $this->rescoreMass($peptide, $precursorMass, $modToMass);
        }
    }

    /**
     * Down ranks labels
     *
     * @param Peptide $peptide
     * @param unknown $labelledMods
     */
    private function rescoreModifications(Peptide $peptide, $labelledMods)
    {
        $boostRate = 0.7;

        foreach ($peptide->getModifications() as $modification) {
            if (in_array($modification->getId(), $labelledMods)) {
                $peptide->setScore($peptide->getScore() * $boostRate, $peptide->getIonsMatched());
            }
        }
    }

    private function rescoreMass(Peptide $peptide, $precursorMass, $modToMass)
    {
        $peptideMass = $this->adodb->GetOne(
            'SELECT `mass_modified` FROM `fasta_peptides` WHERE `job` = ' . $this->jobId . ' && `id` = ' .
            $peptide->getId());

        if (is_null($peptideMass)) {
            throw new \OutOfBoundsException('Peptide "' . $peptide->getId() . '" not found');
        }

        $modificationMass = 0;
        foreach ($peptide->getModifications() as $modification) {
            $modificationMass += $modToMass[$modification->getId()];
        }

        $peptideMass += $modificationMass;

        // Calculate ppm
        $ppm = abs(($peptideMass - $precursorMass) / $peptideMass * 1000000);
        if ($ppm <= 10) {
            if ($ppm < 0.1) {
                $ppm = 0.1;
            }

            $boostFunc = - 1.5 * log($ppm, 2) + 5.017;
            $boostRate = 1 + ($boostFunc / 100);

            $peptide->setScore($peptide->getScore() * $boostRate, $peptide->getIonsMatched());
        }
    }
}
