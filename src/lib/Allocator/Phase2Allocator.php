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
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\crowdsource\Core\Modification;

class Phase2Allocator extends AbstractAllocator implements AllocatorInterface
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
        
        $this->setPhase(2);
        $this->setWorkUnitKeys('precursor');
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \pgb_liv\crowdsource\Allocator\AllocatorInterface::getWorkUnit()
     */
    public function getWorkUnit()
    {
        $toleranceRaw = $this->adodb->GetRow(
            'SELECT `fragment_tolerance`, `fragment_tolerance_unit` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        
        if (empty($toleranceRaw)) {
            return false;
        }
        
        $tolerance = new Tolerance((float) $toleranceRaw['fragment_tolerance'], $toleranceRaw['fragment_tolerance_unit']);
        
        // Select any jobs unassigned or assigned but not completed in the past minute
        $precursorId = $this->adodb->GetOne(
            'SELECT `precursor` FROM `workunit2` WHERE `job` =' . $this->jobId .
                 ' && (`status` = \'UNASSIGNED\' || ( `completed_at` IS NULL && `assigned_at` < NOW() - INTERVAL 1 MINUTE)) LIMIT 0, 1');
        
        if (is_null($precursorId)) {
            // All work units are possibly complete
            if ($this->isPhaseComplete()) {
                $this->setJobDone();
            }
            
            return false;
        }
        
        $workUnit = new WorkUnit($this->jobId, (int) $precursorId);
        $workUnit->setFragmentTolerance($tolerance);
        
        $this->injectFragmentIons($workUnit);
        $this->injectPeptides($workUnit);
        $this->injectFixedModifications($workUnit);
        
        return $workUnit;
    }

    private function injectPeptides(WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `p`.`id`, `p`.`peptide`, `w`.`modification`,`u`.`mono_mass`, `w`.`count` FROM `fasta_peptides` AS `p`
            LEFT JOIN `workunit2_peptides` AS `w` ON `w`.`peptide`=`p`.`id`
            LEFT JOIN `unimod_modifications` AS `u` ON `u`.`record_id`=`w`.`modification`
            WHERE `w`.`job` = ' . $workUnit->getJobId() .
                 ' && `w`.`precursor`=' . $workUnit->getPrecursorId());
        
        foreach ($rs as $record) {
            $peptide = new Peptide((int) $record['id']);
            $peptide->setSequence($record['peptide']);
            $workUnit->addPeptide($peptide);
            
            $residues = $this->adodb->GetCol(
                'SELECT `one_letter` FROM `unimod_specificity` WHERE `mod_key` = ' . $record['modification'] .
                     ' && `classifications_key` = 2');
            foreach ($residues as $key => $residue) {
                if ($residue == 'C-term') {
                    $residues[$key] = ']';
                } elseif ($residue == 'N-term') {
                    $residues[$key] = '[';
                }
            }
            
            for ($modIndex = 0; $modIndex < $record['count']; $modIndex ++) {
                $modification = new Modification((int) $record['modification']);
                $modification->setMonoisotopicMass((float) $record['mono_mass']);
                $modification->setResidues($residues);
                
                $peptide->addModification($modification);
            }
        }
    }

    public function setWorkUnitResults(WorkUnit $workUnit)
    {
        foreach ($workUnit->getPeptides() as $peptide) {
            $this->recordPeptideScores($workUnit->getPrecursorId(), $peptide);
        }
        
        // Mark work unit as complete
        $this->adodb->Execute(
            'UPDATE `workunit2` SET `status` = \'COMPLETE\', `completed_at` = NOW() WHERE `precursor` = ' .
                 $workUnit->getPrecursorId() . ' && `job` =' . $this->jobId);
    }

    private function recordPeptideScores($precursorId, Peptide $peptide)
    {
        // only place the score if > 0
        if (is_null($peptide->getScore()) || $peptide->getScore() <= 0) {
            return;
        }
        
        $mod = current($peptide->getModifications());
        $modCount = count($peptide->getModifications());
        $this->adodb->Execute(
            'UPDATE `workunit2_peptides` SET `score` = ' . $peptide->getScore() . ', `ions_matched` = ' .
                 $peptide->getIonsMatched() . ' WHERE `job` = ' . $this->jobId . ' && `precursor` = ' . $precursorId .
                 ' && `peptide` = ' . $peptide->getId() . ' && `modification` = ' . $mod->getId() . ' && `count` = ' .
                 $modCount);
        
        foreach ($peptide->getModifications() as $mod) {
            $this->adodb->Execute(
                'INSERT INTO `workunit2_peptide_locations` (`job`, `precursor`, `peptide`, `modification`, `count`, `location`) VALUES (' .
                     $this->jobId . ', ' . $precursorId . ', ' . $peptide->getId() . ',' . $mod->getId() . ', ' .
                     $modCount . ', ' . $mod->getLocation() . ')');
        }
    }

    public function setWorkUnitWorker($workerId, WorkUnit $workUnit)
    {
        $this->recordWorkUnitWorker($workerId, $workUnit->getPrecursorId());
    }
}
