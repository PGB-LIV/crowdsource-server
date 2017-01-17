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

use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Core\Protein;
use pgb_liv\php_ms\Utility\Digest\DigestFactory;
use pgb_liv\php_ms\Utility\Filter\FilterLength;
use pgb_liv\crowdsource\BulkQuery;

class DatabasePreprocessor
{

    private $peptide2Id = array();

    private $adodb;

    private $databaseParser;

    private $jobId;

    private $enzyme;

    private $cleaver;

    private $filter;

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

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn            
     * @param \Iterator $rawParser            
     * @param \int $jobId            
     */
    public function __construct($conn, FastaReader $databaseParser, $jobId)
    {
        $this->adodb = $conn;
        $this->databaseParser = $databaseParser;
        $this->jobId = $jobId;
    }

    private function initialise()
    {
        $this->setEnzyme();
        
        $this->setMaxMissedCleavage();
        
        $this->cleaver = DigestFactory::getDigest($this->enzyme);
        $this->cleaver->setMaxMissedCleavage($this->maxMissedCleavage);
        
        $this->filter = new FilterLength($this->minPeptideLength, $this->maxPeptideLength);
    }

    private function setMaxMissedCleavage()
    {
        // TODO: Pull from database
        $this->maxMissedCleavage = 2;
    }

    private function setEnzyme()
    {
        // TODO: Pull from database
        $this->enzyme = 'Trypsin';
    }

    public function process()
    {
        $this->initialise();
        
        $this->proteinBulk = new BulkQuery($this->adodb, 'INSERT IGNORE INTO `fasta_proteins` (`id`, `job`, `description`, `sequence`) VALUES ');
        $this->peptideBulk = new BulkQuery($this->adodb, 
            'INSERT IGNORE INTO `fasta_peptides` (`id`, `job`, `peptide`, `length`, `missed_cleavage`, `mass`) VALUES');
        $this->protein2peptideBulk = new BulkQuery($this->adodb, 'INSERT INTO `fasta_protein2peptide` (`job`, `protein`, `peptide`, `position_start`) VALUES ');
        
        foreach ($this->databaseParser as $databaseEntry) {
            $proteinId = $this->processProtein($databaseEntry);
            $this->processPeptides($proteinId, $databaseEntry);
        }
        
        $this->proteinBulk->close();
        $this->peptideBulk->close();
        $this->protein2peptideBulk->close();
        
        // Clear memory
        $this->peptide2Id = array();
    }

    private function processProtein(Protein $protein)
    {
        $proteinId = $this->proteinId;
        $this->proteinBulk->append(
            sprintf('(%d, %d, %s, %s)', $proteinId, $this->jobId, $this->adodb->quote($protein->getDescription()), $this->adodb->quote($protein->getSequence())));
        
        $this->proteinId ++;
        
        return $proteinId;
    }

    private function processPeptides($proteinId, $protein)
    {
        $peptides = $this->cleaver->digest($protein);
        $peptides = $this->filter->filter($peptides);
        
        foreach ($peptides as $peptide) {
            if (! isset($this->peptide2Id[$peptide->getSequence()])) {
                $this->peptideBulk->append(
                    sprintf('(%d, %d, %s, %d, %d, %f)', $this->peptideId, $this->jobId, $this->adodb->quote($peptide->getSequence()), $peptide->getLength(), 
                        $peptide->getMissedCleavageCount(), $peptide->calculateMass()));
                
                $this->peptide2Id[$peptide->getSequence()] = $this->peptideId;
                
                $this->peptideId ++;
            }
            
            $peptideId = $this->peptide2Id[$peptide->getSequence()];
            
            $this->protein2peptideBulk->append(sprintf('(%d, %d, %d, %d)', $this->jobId, $proteinId, $peptideId, $peptide->getPositionStart()));
        }
    }
}
