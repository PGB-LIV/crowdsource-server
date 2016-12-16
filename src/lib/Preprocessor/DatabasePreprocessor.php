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
namespace PGB_LIV\CrowdSource\Preprocessor;

use PGB_LIV\CrowdSource\BulkQuery;

class DatabasePreprocessor
{

    private $adodb;

    private $databaseParser;

    private $jobId;

    private $cleavageRule;

    private $aminoAcids;

    private $maxMissedCleavage;
    
    // TODO: pull from database
    private $minPeptideLength = 6;
    
    // TODO: pull from database
    private $maxPeptideLength = 60;

    private $peptideId = 1;

    private $proteinId = 1;

    private $proteinBulk;

    private $peptideBulk;

    private $protein2peptideBulk;

    const HYDROGEN_MASS = 1.007825;

    const OXYGEN_MASS = 15.994915;

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn            
     * @param \Iterator $rawParser            
     * @param \int $jobId            
     */
    public function __construct($conn, $databaseParser, $jobId)
    {
        $this->adodb = $conn;
        $this->databaseParser = $databaseParser;
        $this->jobId = $jobId;
    }

    private function initialise()
    {
        $this->initialiseAminoAcidsTable();
        
        $this->setEnzyme();
        
        $this->setMaxMissedCleavage();
    }

    private function initialiseAminoAcidsTable()
    {
        $this->aminoAcids = $this->adodb->getAssoc('SELECT `one_letter`, `mono_mass` FROM `unimod`.`amino_acids`;');
        $this->aminoAcids['X'] = 0;
    }

    private function setMaxMissedCleavage()
    {
        // TODO: Pull from database
        $this->maxMissedCleavage = 2;
    }

    private function setEnzyme()
    {
        // TODO: Pull from database
        $this->cleavageRule = '/(?!P)(?<=[RK])/';
    }

    public function process()
    {
        $this->initialise();
        
        $this->proteinBulk = new BulkQuery($this->adodb, 'INSERT IGNORE INTO `fasta_proteins` (`id`, `job`, `description`, `sequence`) VALUES ');
        $this->peptideBulk = new BulkQuery($this->adodb, 
            'INSERT IGNORE INTO `fasta_peptides` (`id`, `job`, `peptide`, `length`, `missed_cleavage`, `mass`) VALUES');
        $this->protein2peptideBulk = new BulkQuery($this->adodb, 
            'INSERT IGNORE INTO `fasta_protein2peptide` (`job`, `protein`, `peptide`, `position_start`) VALUES ');
        
        foreach ($this->databaseParser as $databaseEntry) {
            $proteinId = $this->processProtein($databaseEntry);
            $this->processPeptides($proteinId, $databaseEntry);
        }
        
        $this->proteinBulk->close();
        $this->peptideBulk->close();
        $this->protein2peptideBulk->close();
    }

    private function processProtein($databaseEntry)
    {
        $this->proteinBulk->append(
            sprintf('(%d, %d, %s, %s)', $this->proteinId, $this->jobId, $this->adodb->quote($databaseEntry['description']), 
                $this->adodb->quote($databaseEntry['sequence'])));
        
        return $this->proteinId ++;
    }

    private function filterPeptides($peptides)
    {
        foreach ($peptides as $key => $peptide) {
            $peptideLength = strlen($peptide['sequence']);
            
            if ($peptideLength < $this->minPeptideLength || $peptideLength > $this->maxPeptideLength) {
                unset($peptides[$key]);
                continue;
            }
            
            $peptides[$key]['mass'] = $this->calculateMass($peptide['sequence']);
        }
        
        return $peptides;
    }

    private function processPeptides($proteinId, $databaseEntry)
    {
        $peptides = $this->cleaveSequence($databaseEntry['sequence']);
        $peptides = $this->filterPeptides($peptides);
        
        foreach ($peptides as $peptide) {
            $this->peptideBulk->append(
                sprintf('(%d, %d, %s, %d, %d, %f)', $this->peptideId, $this->jobId, $this->adodb->quote($peptide['sequence']), strlen($peptide['sequence']), 
                    $peptide['missedCleavage'], $peptide['mass']));
            
            $this->protein2peptideBulk->append(sprintf('(%d, %d, %d, %d)', $this->jobId, $proteinId, $this->peptideId, $peptide['start']));
            
            $this->peptideId ++;
        }
    }

    /**
     * Calculates the neutral mass of a sequence
     * 
     * @param string $sequence
     *            The peptide sequence to calculate for
     * @return The neutral mass of the sequence
     */
    private function calculateMass($sequence)
    {
        $acids = str_split($sequence, 1);
        
        $mass = self::HYDROGEN_MASS + self::HYDROGEN_MASS + self::OXYGEN_MASS;
        foreach ($acids as $acid) {
            $mass += $this->aminoAcids[$acid];
        }
        
        return $mass;
    }

    private function cleaveSequence($proteinSequence)
    {
        $peptideSequences = preg_split($this->cleavageRule, $proteinSequence);
        
        $peptides = array();
        $position = 0;
        foreach ($peptideSequences as $peptideSequence) {
            $peptide = array();
            $peptide['start'] = $position;
            $peptide['sequence'] = $peptideSequence;
            $peptide['finish'] = $position + strlen($peptideSequence) - 1;
            $peptide['missedCleavage'] = 0;
            
            $peptides[] = $peptide;
            $position = $peptide['finish'] + 1;
        }
        
        $missedCleaveges = array();
        
        // Factor in missed cleaves
        for ($index = 0; $index < count($peptides); $index ++) {
            $peptide = $peptides[$index]; // Copy peptide
            
            for ($missedCleave = 1; $missedCleave <= $this->maxMissedCleavage; $missedCleave ++) {
                if ($index + $missedCleave >= count($peptides)) {
                    continue;
                }
                
                $peptide['sequence'] .= $peptides[$index + $missedCleave]['sequence'];
                $peptide['finish'] = $peptide['start'] + strlen($peptide['sequence']) - 1;
                $peptide['missedCleavage'] = $missedCleave;
                $missedCleaveges[] = $peptide;
            }
        }
        
        return array_merge($peptides, $missedCleaveges);
    }
}
