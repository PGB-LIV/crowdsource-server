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
        $workUnit = new WorkUnit();
        
        $workUnit->job = $this->jobId;
        
        // Select any jobs unassigned or assigned but not completed in the past minute
        $ms1 = $this->adodb->GetOne(
            'SELECT `ms1` FROM `workunit1` WHERE `job` =' . $this->jobId .
                 ' && (`status` = \'UNASSIGNED\' || ( `completed_at` IS NULL && `assigned_at` < NOW() - INTERVAL 1 MINUTE)) LIMIT 0, 1');
        
        if (is_null($ms1)) {
            // All work units are possibly complete
            if ($this->isPhaseComplete()) {
                $this->setJobDone();
            }
            
            return false;
        }
        
        $workUnit->ms1 = (int) $ms1;
        $workUnit->mods = $this->getFixedModifications();
        $workUnit->ms2 = $this->getMs2($workUnit->job, $workUnit->ms1);
        
        // get the peptides array from workunit_peptides
        $workUnit->peptides = $this->getPeptides($workUnit->ms1);
        
        return $workUnit;
    }

    private function getPeptides($precusorId)
    {
        $peptides = array();
        $rs = $this->adodb->Execute(
            'SELECT `fpeps`.`id`, `fpeps`.`peptide` FROM `fasta_peptides` AS `fpeps`
            LEFT OUTER JOIN `workunit1_peptides` AS `wu_p` ON `wu_p`.`peptide`=`fpeps`.`id`
            WHERE `wu_p`.`job` = ' . $this->jobId .
                 ' && `wu_p`.`ms1`=' . $precusorId);
        $i = 0;
        
        while (! $rs->EOF) {
            $peptides[$i]['id'] = (int) $rs->fields['id'];
            $peptides[$i]['structure'] = $rs->fields['peptide'];
            $rs->MoveNext();
            $i ++;
        }
        
        return $peptides;
    }

    private function getFixedModifications()
    {
        $this->modsForCurrentJob = array();
        
        $rs = $this->adodb->Execute(
            'SELECT `unimod_modifications`.`mono_mass`, `job_fixed_mod`.`acid` FROM `job_fixed_mod`
    INNER JOIN `unimod_modifications` ON `unimod_modifications`.`record_id` = `job_fixed_mod`.`mod_id` WHERE 
            `job_fixed_mod`.`job` = ' . $this->jobId);
        
        $i = 0;
        while (! $rs->EOF) {
            $this->modsForCurrentJob[$i]['modtype'] = 'fixed';
            $this->modsForCurrentJob[$i]['modmass'] = (float) $rs->fields['mono_mass'];
            $this->modsForCurrentJob[$i]['loc'] = $rs->fields['acid'];
            $rs->MoveNext();
            $i ++;
        }
        
        return $this->modsForCurrentJob;
    }
    
    //
    /**
     * Get the MS/MS data for the associated precursor mass ID
     *
     * @param int $ms1
     *            Precursor mass ID to search for
     * @return array MS/MS m/z and intensity data
     */
    private function getMs2($ms1)
    {
        if (! is_int($ms1)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($ms1));
        }
        
        $ms2 = array();
        
        $rs = $this->adodb->Execute(
            'SELECT `mz`, `intensity` FROM `raw_ms2` WHERE `job` = ' . $this->jobId . ' && `ms1` = ' . $ms1);
        $i = 0;
        while (! $rs->EOF) {
            $ms2[$i]['mz'] = (float) $rs->fields['mz'];
            $ms2[$i]['intensity'] = (float) $rs->fields['intensity'];
            $rs->MoveNext();
            $i ++;
        }
        
        return $ms2;
    }

    public function setWorkUnitResults($results)
    {
        foreach ($results->peptides as $result) {
            $this->recordPeptideScores((int) $results->ms1, $result);
        }
        
        // Mark work unit as complete
        $this->adodb->Execute(
            'UPDATE `workunit1` SET `status` = \'COMPLETE\', `completed_at` = NOW() WHERE `id` = ' . $results->ms1 .
                 ' && `job` =' . $this->jobId);
    }

    private function recordPeptideScores($ms1Id, $peptide)
    {
        if (! is_int($ms1Id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($ms1Id));
        }
        
        // only place the score if > 0
        if ($peptide->score <= 0) {
            return;
        }
        
        $this->adodb->Execute(
            'UPDATE `workunit1_peptides` SET `score` = ' . $this->adodb->quote($peptide->score) . ' WHERE `job` = ' .
                 $this->jobId . ' && `ms1` = ' . $ms1Id . ' && `peptide` = ' . $this->adodb->quote($peptide->id));
    }
}
