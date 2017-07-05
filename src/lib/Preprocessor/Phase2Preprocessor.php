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
namespace pgb_liv\crowdsource\Preprocessor;

use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\crowdsource\Core\Modification;

/**
 * Logic for performing all phase 2 preprocessing
 *
 * @author Andrew Collins
 */
class Phase2Preprocessor extends AbstractPreprocessor
{

    const POSITION_ANY = 2;

    const POSITION_N_TERM = 3;

    const POSITION_C_TERM = 4;

    const POSITION_PROT_N_TERM = 5;

    const POSITION_PROT_C_TERM = 6;

    /**
     * Precursor mass tolerance as ppm value
     *
     * @var Tolerance
     */
    private $massTolerance;

    /**
     * Map of one-letter residue chars to mod ids
     *
     * @var array
     */
    private $residueToModifications;

    /**
     * Map of mod ids to mod masses
     *
     * @var array
     */
    private $modToMass;

    private $maxMods;

    /**
     * Starts the indexing all phase 2 data
     */
    public function process()
    {
        $this->initialise(2);
        echo 'Pre-processing work units.' . PHP_EOL;
        $this->indexWorkUnits();
        
        $this->finalise();
    }

    protected function initialise($phase)
    {
        parent::initialise($phase);
        // Force to only one mod for now
        $this->maxMods = 1; // min(MAX_MOD_PER_TYPE, MAX_MOD_TOTAL);
        
        $toleranceRaw = $this->adodb->GetRow(
            'SELECT `precursor_tolerance`, `precursor_tolerance_unit` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        
        $this->massTolerance = new Tolerance((float) $toleranceRaw['precursor_tolerance'], 
            $toleranceRaw['precursor_tolerance_unit']);
        
        $this->indexModifications();
    }

    /**
     * Creates an index of all phase 2 work units
     */
    private function indexWorkUnits()
    {
        $ptmCandidates = $this->getPtmCandidates();
        
        foreach ($ptmCandidates as $ptmCandidate) {
            $sequence = $ptmCandidate['peptide'];
            $peptideMass = $ptmCandidate['mass_modified'];
            
            $possibleMods = $this->getPossibleModifications($sequence, $ptmCandidate['start']);
            
            $this->findPrecursorMatches($possibleMods, $peptideMass, $ptmCandidate['id']);
        }
        
        $this->addWorkUnitPrecursors();
    }

    /**
     * Creates an index of all possible PTMs an array
     *
     * @return array of residue => array(id, mass)
     */
    private function indexModifications()
    {
        $rs = $this->adodb->Execute('SELECT `record_id`, `mono_mass` FROM `unimod_modifications` WHERE `approved` = 1');
        
        $this->residueToModifications = array();
        $this->residueToModifications[Phase2Preprocessor::POSITION_ANY] = array();
        $this->residueToModifications[Phase2Preprocessor::POSITION_N_TERM] = array();
        $this->residueToModifications[Phase2Preprocessor::POSITION_C_TERM] = array();
        $this->residueToModifications[Phase2Preprocessor::POSITION_PROT_N_TERM] = array();
        $this->residueToModifications[Phase2Preprocessor::POSITION_PROT_C_TERM] = array();
        $this->modToMass = array();
        
        foreach ($rs as $modification) {
            $residues = $this->adodb->GetAll(
                'SELECT `one_letter`, `position_key` FROM `unimod_specificity` WHERE `hidden` = 0 && `mod_key` = ' .
                     $modification['record_id']);
            
            if (empty($residues)) {
                $residues = $this->adodb->GetAll(
                    'SELECT `one_letter`, `position_key` FROM `unimod_specificity` WHERE `mod_key` = ' .
                         $modification['record_id']);
            }
            
            foreach ($residues as $residue) {
                $position = $residue['position_key'];
                $residue = $residue['one_letter'];
                
                if (! isset($this->residueToModifications[$position][$residue])) {
                    $this->residueToModifications[$position][$residue] = array();
                }
                
                $this->residueToModifications[$position][$residue][] = $modification['record_id'];
                $this->modToMass[$modification['record_id']] = array();
                
                for ($count = 1; $count <= $this->maxMods; $count ++) {
                    $this->modToMass[$modification['record_id']][$count] = $modification['mono_mass'] * $count;
                }
            }
        }
    }

    /**
     * Gets the set of possible peptide candidates that should be searched for PTM sites
     *
     * @return unknown
     */
    private function getPtmCandidates()
    {
        $scoreThreshold = 30;
        
        // Select best peptides
        return $this->adodb->Execute(
            'SELECT `f`.`id`, `f`.`peptide`, `f`.`mass_modified`, MIN(`p2p`.`position_start`) `start` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_peptides` `f` ON `f`.`id` = `p2p`.`peptide` && `f`.`job` = `p2p`.`job` WHERE `protein` IN (SELECT DISTINCT `protein` FROM `fasta_protein2peptide` WHERE `peptide` IN (SELECT `peptide` FROM `workunit1_peptides` WHERE `job` = ' .
                 $this->jobId . ' && `score` > ' . $scoreThreshold . ') && `job` =  ' . $this->jobId .
                 ' ) && `p2p`.`job` = ' . $this->jobId . ' GROUP BY `f`.`id`');
    }

    /**
     * Finds all possible modifications which may occur in this peptide sequence.
     *
     * @param string $sequence
     *            The peptide sequence to test against
     * @return array A 2D array of modId => mod count
     */
    private function getPossibleModifications($sequence, $startPosition)
    {
        $possibleMods = array();
        foreach ($this->residueToModifications[Phase2Preprocessor::POSITION_N_TERM] as $residue => $mods) {
            if ($residue == 'N-term' || $residue == $sequence[0]) {
                $this->addMods($possibleMods, $mods);
            }
        }
        
        if ($startPosition == 0) {
            foreach ($this->residueToModifications[Phase2Preprocessor::POSITION_PROT_N_TERM] as $residue => $mods) {
                if ($residue == 'N-term' || $residue == $sequence[0]) {
                    $this->addMods($possibleMods, $mods);
                }
            }
        }
        
        $last = strlen($sequence) - 1;
        foreach ($this->residueToModifications[Phase2Preprocessor::POSITION_C_TERM] as $residue => $mods) {
            if ($residue == 'C-term' || $residue == $sequence[$last]) {
                $this->addMods($possibleMods, $mods);
            }
        }
        
        // TODO: We don't currently know where the sequence is in a peptide
        // foreach ($this->residueToModifications[Phase2Preprocessor::POSITION_PROT_C_TERM] as $residue => $mods) {
        // if ($residue == 'C-term' || $residue == $sequence[0]) {
        // $this->addMods($possibleMods, $mods);
        // }
        // }
        
        $residues = count_chars($sequence, 1);
        foreach ($this->residueToModifications[Phase2Preprocessor::POSITION_ANY] as $residue => $mods) {
            if ($residue == 'C-term' || $residue == 'N-term' || isset($residues[ord($residue)])) {
                $this->addMods($possibleMods, $mods);
            }
        }
        
        return $possibleMods;
    }

    private function addMods(&$possibleMods, $mods)
    {
        foreach ($mods as $modId) {
            if (! isset($possibleMods[$modId])) {
                $possibleMods[$modId] = 0;
            }
            
            $possibleMods[$modId] ++;
        }
    }

    /**
     * Iterates over each possible modification and calls findPrecursors()
     *
     * @param array $possibleMods
     *            array of possible modifcations
     * @param int $peptideId
     *            Mass of the peptide to match against
     * @param int $peptideId
     *            ID of the peptide being matched against
     */
    private function findPrecursorMatches($possibleMods, $peptideMass, $peptideId)
    {
        echo 'Searching #' . $peptideId . ' for ' . count($possibleMods) . ' possible mods' . PHP_EOL;
        foreach ($possibleMods as $modId => $maxMods) {
            
            if ($maxMods > $this->maxMods) {
                $maxMods = $this->maxMods;
            }
            
            for ($modCount = 1; $modCount <= $maxMods; $modCount ++) {
                $ratio = $this->modToMass[$modId][$modCount] / $peptideMass;
                
                // Skip huge mods
                if ($ratio > .15)
                {
                    continue;
                }
                
                $totalMass = $peptideMass + $this->modToMass[$modId][$modCount];
                
                $this->findPrecursors($totalMass, $peptideId, $modId, $modCount);
            }
        }
    }

    /**
     * Finds the spectra that are within tolerance of the peptide and modification, and then inserts into the database
     *
     * @param float $peptideMass
     *            Mass of the peptide to match against
     * @param int $peptideId
     *            ID of the peptide being matched against
     * @param int $modId
     *            ID of the modification being matched against
     * @param int $modCount
     *            Number of modifications occuring
     */
    private function findPrecursors($peptideMass, $peptideId, $modId, $modCount)
    {
        $tolerance = $this->massTolerance->getDaltonDelta($peptideMass);
        $pepMassLow = $peptideMass - $tolerance;
        $pepMassHigh = $peptideMass + $tolerance;
        
        $this->adodb->Execute(
            'INSERT IGNORE INTO `workunit2_peptides` (`job`, `precursor`, `peptide`, `modification`, `count`) SELECT ' .
                 $this->jobId . ', `id`, ' . $peptideId . ', ' . $modId . ', ' . $modCount .
                 ' FROM `raw_ms1` WHERE `job` = ' . $this->jobId . ' && `mass` BETWEEN ' . $pepMassLow . ' AND ' .
                 $pepMassHigh);
    }

    /**
     * Pulls distinct set of precursor ID's from the peptides workunit table and creates parent workunit entries in database
     */
    private function addWorkUnitPrecursors()
    {
        $this->adodb->Execute(
            'INSERT INTO `workunit2` (`job`, `precursor`) SELECT ' . $this->jobId .
                 ', `precursor` FROM `workunit2_peptides` GROUP BY `precursor` ORDER BY `precursor`');
    }
}
