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
use pgb_liv\crowdsource\Core\Phase1WorkUnit;

class Phase1Allocator extends AbstractAllocator implements AllocatorInterface
{

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
        $this->setWorkUnitKeys('ms1');
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \pgb_liv\crowdsource\Allocator\AllocatorInterface::getWorkUnit()
     */
    public function getWorkUnit()
    {
        // Select any jobs unassigned or assigned but not completed in the past minute
        $precursorId = $this->adodb->GetOne(
            'SELECT `ms1` FROM `workunit1` WHERE `job` =' . $this->jobId .
                 ' && (`status` = \'UNASSIGNED\' || ( `completed_at` IS NULL && `assigned_at` < NOW() - INTERVAL 1 MINUTE)) LIMIT 0, 1');
        
        if (is_null($precursorId)) {
            // All work units are possibly complete
            if ($this->isPhaseComplete()) {
                $this->setJobDone();
            }
            
            return false;
        }
        
        $workUnit = new Phase1WorkUnit((int) $this->jobId, (int) $precursorId);
        
        $this->injectFragmentIons($workUnit);
        $this->injectPeptides($workUnit);
        $this->injectFixedModifications($workUnit);
        
        return $workUnit;
    }

    private function injectPeptides(Phase1WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `fpeps`.`id`, `fpeps`.`peptide` FROM `fasta_peptides` AS `fpeps`
            LEFT OUTER JOIN `workunit1_peptides` AS `wu_p` ON `wu_p`.`peptide`=`fpeps`.`id`
            WHERE `wu_p`.`job` = ' . $workUnit->getJobId() .
                 ' && `wu_p`.`ms1`=' . $workUnit->getPrecursorId());
        
        foreach ($rs as $record) {
            $workUnit->addPeptide((int) $record['id'], $record['peptide']);
        }
    }

    private function injectFixedModifications(Phase1WorkUnit $workUnit)
    {
        $modifications = array();
        
        $rs = $this->adodb->Execute(
            'SELECT `unimod_modifications`.`mono_mass`, `job_fixed_mod`.`acid` FROM `job_fixed_mod`
    INNER JOIN `unimod_modifications` ON `unimod_modifications`.`record_id` = `job_fixed_mod`.`mod_id` WHERE 
            `job_fixed_mod`.`job` = ' . $workUnit->getJobId());
        
        foreach ($rs as $record) {
            $workUnit->addFixedModification((float) $record['mono_mass'], $record['acid']);
        }
    }

    /**
     * Injects the MS/MS data into the work unit object
     *
     * @param Phase1WorkUnit $workUnit
     *            Work unit to inject into
     *            
     */
    private function injectFragmentIons(Phase1WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `mz`, `intensity` FROM `raw_ms2` WHERE `job` = ' . $workUnit->getJobId() . ' && `ms1` = ' .
                 $workUnit->getPrecursorId());
        
        foreach ($rs as $record) {
            $workUnit->addFragmentIon((float) $record['mz'], (float) $record['intensity']);
        }
    }

    public function setWorkUnitResults(Phase1WorkUnit $workUnit)
    {
        foreach ($workUnit->getPeptides() as $peptide) {
            $this->recordPeptideScores($workUnit->getPrecursorId(), $peptide);
        }
        
        // Mark work unit as complete
        $this->adodb->Execute(
            'UPDATE `workunit1` SET `status` = \'COMPLETE\', `completed_at` = NOW() WHERE `ms1` = ' .
                 $workUnit->getPrecursorId() . ' && `job` =' . $this->jobId);
    }

    private function recordPeptideScores($ms1Id, $peptide)
    {
        if (! is_int($ms1Id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($ms1Id));
        }
        
        // only place the score if > 0
        if ($peptide['score'] <= 0) {
            return;
        }
        
        $this->adodb->Execute(
            'UPDATE `workunit1_peptides` SET `score` = ' . $this->adodb->quote($peptide->score) . ' WHERE `job` = ' .
                 $this->jobId . ' && `ms1` = ' . $ms1Id . ' && `peptide` = ' . $this->adodb->quote($peptide->id));
    }
}
