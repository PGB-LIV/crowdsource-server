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
     * @var float
     */
    private $massTolerance;

    /**
     * Map of one-letter residue chars to modifications
     *
     * @var array
     */
    private $residueToModifications;

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
        $this->massTolerance = $this->adodb->GetOne(
            'SELECT `mass_tolerance` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        // As ppm
        $this->massTolerance /= 1000000;
        
        $this->residueToModifications = $this->indexModifications();
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
     * Calculates the ppm tolerance of the specified mass
     *
     * @param float $mass
     *            Mass to calculate tolerance for
     * @return float Tolerance value
     */
    private function calculateTolerance($mass)
    {
        return $mass * $this->massTolerance;
    }

    /**
     * Creates an index of all possible PTMs an array
     *
     * @return array of residue => array(id, mass)
     */
    private function indexModifications()
    {
        $rs = $this->adodb->Execute(
            'SELECT `m`.`record_id`, `one_letter`, `mono_mass` FROM `unimod_specificity` `s` LEFT JOIN `unimod_modifications` `m` ON `s`.`mod_key` = `m`.`record_id` WHERE `classifications_key` = 2');
        
        $residueToMod = array();
        
        foreach ($rs as $record) {
            $residue = $record['one_letter'];
            if (! isset($residueToMod[$residue])) {
                $residueToMod[$residue] = array();
            }
            
            $mod = array(
                'id' => $record['record_id'],
                'mass' => $record['mono_mass']
            );
            $residueToMod[$residue][] = $mod;
        }
        
        return $residueToMod;
    }

    /**
     * Gets the set of possible peptide candidates that should be searched for PTM sites
     *
     * @return unknown
     */
    private function getPtmCandidates()
    {
        // Select best peptides
        return $this->adodb->Execute(
            'SELECT `f`.`id`, `f`.`peptide`, `f`.`mass_modified`, MAX(`score`) AS `bestscore` FROM `workunit1_peptides` `w` LEFT JOIN `fasta_peptides` `f` ON `f`.`id` = `w`.`peptide` WHERE `w`.`job` = ' .
                 $this->jobId . ' GROUP BY `w`.`peptide` HAVING `bestscore` >= 5 ORDER BY `f`.`id` ASC');
    }

    /**
     * Finds all possible modifications which may occur in this peptide sequence.
     *
     * @param string $sequence
     *            The peptide sequence to test against
     * @return array A 2D array of modId => mod mono mass
     */
    private function getPossibleModifications($sequence)
    {
        $possibleMods = array();
        for ($i = 0; $i < strlen($sequence); $i ++) {
            $residue = $sequence[$i];
            
            if (! isset($this->residueToModifications[$residue])) {
                continue;
            }
            
            foreach ($this->residueToModifications[$residue] as $mods) {
                $possibleMods[$mods['id']] = $mods['mass'];
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
        echo 'Searching ' . $peptideId . ' for ' . count($possibleMods) . ' possible mods' . PHP_EOL;
        foreach ($possibleMods as $modId => $modMass) {
            $totalMass = $peptideMass + $modMass;
            
            $this->findPrecursors($totalMass, $peptideId, $modId);
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
     */
    private function findPrecursors($peptideMass, $peptideId, $modId)
    {
        $tolerance = $this->calculateTolerance($peptideMass);
        $pepMassLow = $peptideMass - $tolerance;
        $pepMassHigh = $peptideMass + $tolerance;
        
        $this->adodb->Execute(
            'INSERT IGNORE INTO `workunit2_peptides` (`job`, `precursor`, `peptide`, `modification`) SELECT ' .
                 $this->jobId . ', `id`, ' . $peptideId . ', ' . $modId . ' FROM `raw_ms1` WHERE `job` = ' . $this->jobId .
                 ' && `mass` BETWEEN ' . $pepMassLow . ' AND ' . $pepMassHigh);
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
