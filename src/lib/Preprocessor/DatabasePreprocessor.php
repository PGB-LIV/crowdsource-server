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

class DatabasePreprocessor
{

    private $adodb;

    private $databaseParser;

    private $jobId;

    private $cleavageRule;

    private $aminoAcids;

    private $maxMissedCleavage;

    private $minPeptideLength = 6;

    private $maxPeptideLength = 16;

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
    }

    private function setMaxMissedCleavage()
    {
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
        
        foreach ($this->databaseParser as $databaseEntry) {
            $proteinId = $this->processProtein($databaseEntry);
            $proteinId = $this->processPeptides($databaseEntry);
        }
    }

    private function processProtein($databaseEntry)
    {}

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

    private function processPeptides($databaseEntry)
    {
        $peptides = $this->cleaveSequence($databaseEntry['sequence']);
        $peptides = $this->filterPeptides($peptides);
        
        var_dump($peptides);
        exit();
    }

    private function calculateMass($sequence)
    {
        $acids = str_split($sequence, 1);
        
        $mass = 0;
        foreach ($acids as $acid) {
            if (isset($this->aminoAcids[$acid])) {
                $mass += $this->aminoAcids[$acid];
            }
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
            $peptide = array();
            $peptide['start'] = $peptides[$index]['start'];
            $peptide['sequence'] = $peptides[$index]['sequence'];
            
            for ($missedCleave = 1; $missedCleave <= $this->maxMissedCleavage; $missedCleave ++) {
                if (! isset($peptides[$index + $missedCleave])) {
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
