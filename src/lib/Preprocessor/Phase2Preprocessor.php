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

/**
 * Logic for performing all phase 2 preprocessing
 *
 * @author Andrew Collins
 */
class Phase2Preprocessor extends AbstractPreprocessor
{

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
        $this->maxMods = min(MAX_MOD_PER_TYPE, MAX_MOD_TOTAL);
        
        $toleranceValue = $this->adodb->GetOne('SELECT `mass_tolerance` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        
        // As ppm
        $this->massTolerance = new Tolerance((float) $toleranceValue, Tolerance::PPM);
        
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
            
            $possibleMods = $this->getPossibleModifications($sequence);
            
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
        $rs = $this->adodb->Execute(
            'SELECT `m`.`record_id`, `one_letter`, `mono_mass` FROM `unimod_specificity` `s` LEFT JOIN `unimod_modifications` `m` ON `s`.`mod_key` = `m`.`record_id` WHERE `s`.`hidden` = 0');
        
        $this->residueToModifications = array();
        $this->modToMass = array();
        
        foreach ($rs as $record) {
            $residue = $record['one_letter'];
            if (! isset($this->residueToModifications[$residue])) {
                $this->residueToModifications[$residue] = array();
            }
            
            $this->residueToModifications[$residue][] = $record['record_id'];
            $this->modToMass[$record['record_id']] = array();
            
            for ($count = 1; $count <= $this->maxMods; $count ++) {
                $this->modToMass[$record['record_id']][$count] = $record['mono_mass'] * $count;
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
        $scoreThreshold = 20;
        
        // Select best peptides
        return $this->adodb->Execute(
            'SELECT `f`.`id`, `f`.`peptide`, `f`.`mass_modified` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_peptides` `f` ON `f`.`id` = `p2p`.`peptide` && `f`.`job` = `p2p`.`job` WHERE `protein` IN (SELECT DISTINCT `protein` FROM `fasta_protein2peptide` WHERE `peptide` IN (SELECT `peptide` FROM `workunit1_peptides` WHERE `job` = ' .
                 $this->jobId . ' && `score` > ' . $scoreThreshold . ') && `job` =  ' . $this->jobId . ' ) && `p2p`.`job` = ' .
                 $this->jobId . ' GROUP BY `f`.`id`');
    }

    /**
     * Finds all possible modifications which may occur in this peptide sequence.
     *
     * @param string $sequence
     *            The peptide sequence to test against
     * @return array A 2D array of modId => mod count
     */
    private function getPossibleModifications($sequence)
    {
        $possibleMods = array();
        $residues = count_chars($sequence, 1);
        foreach ($residues as $residue => $frequency) {
            $residue = chr($residue);
            // No modifications for residue
            if (! isset($this->residueToModifications[$residue])) {
                continue;
            }
            
            foreach ($this->residueToModifications[$residue] as $modId) {
                if (! isset($possibleMods[$modId])) {
                    $possibleMods[$modId] = 0;
                }
                
                $possibleMods[$modId] ++;
            }
        }
        
        return $possibleMods;
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
