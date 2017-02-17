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
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Utility\Digest\DigestFactory;
use pgb_liv\php_ms\Utility\Filter\FilterLength;
use pgb_liv\crowdsource\BulkQuery;

class DatabasePreprocessor
{

    private $peptide2Id = array();

    private $adodb;

    private $databaseParser;

    private $jobId;

    private $cleaver;

    private $filter;

    private $peptideId = 1;

    private $proteinId = 1;

    private $proteinBulk;

    private $peptideBulk;

    private $protein2peptideBulk;

    private $modifications;

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn
     *            Open ADOdb connection to database to write to
     * @param FastaReader $databaseParser
     *            Open FastaReader instance to read from
     * @param int $jobId
     *            The job to process
     */
    public function __construct(\ADOConnection $conn, FastaReader $databaseParser, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        $this->adodb = $conn;
        $this->databaseParser = $databaseParser;
        $this->jobId = $jobId;
    }

    private function initialise()
    {
        $job = $this->adodb->GetRow(
            'SELECT `miss_cleave_max`, `peptide_min`, `peptide_max`, `name` AS `enzyme` FROM `job_queue` `j` LEFT JOIN `enzymes` `e` ON `e`.`id` = `j`.`enzyme` WHERE `j`.`id` = ' .
                 $this->jobId);
        
        $this->modifications = $this->adodb->GetAssoc(
            'SELECT `j`.`acid`, `mono_mass` FROM `job_fixed_mod` `j` LEFT JOIN `unimod_modifications` ON `j`.`mod_id` = `record_id` WHERE `j`.`job` = ' .
                 $this->jobId);
        
        $this->cleaver = DigestFactory::getDigest($job['enzyme']);
        $this->cleaver->setMaxMissedCleavage((int) $job['miss_cleave_max']);
        
        $this->filter = new FilterLength($job['peptide_min'], $job['peptide_max']);
        
        $this->proteinBulk = new BulkQuery($this->adodb, 
            'INSERT IGNORE INTO `fasta_proteins` (`id`, `job`, `identifier`, `description`, `sequence`) VALUES ');
        $this->peptideBulk = new BulkQuery($this->adodb, 
            'INSERT IGNORE INTO `fasta_peptides` (`id`, `job`, `peptide`, `length`, `missed_cleavage`, `mass`, `mass_modified`, `is_decoy`) VALUES');
        $this->protein2peptideBulk = new BulkQuery($this->adodb, 
            'INSERT INTO `fasta_protein2peptide` (`job`, `protein`, `peptide`, `position_start`) VALUES ');
    }

    public function process()
    {
        $this->initialise();
        
        // First pass. Insert proteins into database
        foreach ($this->databaseParser as $databaseEntry) {
            $this->processProtein($databaseEntry);
        }
        
        $this->proteinBulk->close();
        
        // Second pass. Insert peptides into database
        $rs = $this->adodb->Execute('SELECT `id`, `sequence` FROM `fasta_proteins` WHERE `job` = ' . $this->jobId);
        foreach ($rs as $record) {
            $protein = new Protein();
            $protein->setSequence($record['sequence']);
            $this->processPeptides($record['id'], $protein);
        }
        
        $this->protein2peptideBulk->close();
        
        // Third pass. Insert decoys into database.
        $rs = $this->adodb->Execute('SELECT `id`, `sequence` FROM `fasta_proteins` WHERE `job` = ' . $this->jobId);
        foreach ($rs as $record) {
            $protein = new Protein();
            $protein->setSequence($record['sequence']);
            $this->processDecoys($protein);
        }
        
        $this->peptideBulk->close();
        
        // Clear memory
        $this->peptide2Id = array();
    }

    private function processProtein(Protein $protein)
    {
        $this->proteinBulk->append(
            sprintf('(%d, %d, %s, %s, %s)', $this->proteinId, $this->jobId, 
                $this->adodb->quote($protein->getUniqueIdentifier()), $this->adodb->quote($protein->getDescription()), 
                $this->adodb->quote($protein->getSequence())));
        
        $this->proteinId ++;
    }

    private function processPeptides($proteinId, $protein)
    {
        $peptides = $this->cleaver->digest($protein);
        $peptides = $this->filter->filterPeptide($peptides);
        
        foreach ($peptides as $peptide) {
            if (! isset($this->peptide2Id[$peptide->getSequence()])) {
                $peptideMass = $peptide->calculateMass();
                $modMass = $this->getModMass($peptide);
                
                $this->peptideBulk->append(
                    sprintf('(%d, %d, %s, %d, %d, %f, %f, 0)', $this->peptideId, $this->jobId, 
                        $this->adodb->quote($peptide->getSequence()), $peptide->getLength(), 
                        $peptide->getMissedCleavageCount(), $peptideMass, $peptideMass + $modMass));
                
                $this->peptide2Id[$peptide->getSequence()] = $this->peptideId;
                
                $this->peptideId ++;
            }
            
            $peptideId = $this->peptide2Id[$peptide->getSequence()];
            
            $this->protein2peptideBulk->append(
                sprintf('(%d, %d, %d, %d)', $this->jobId, $proteinId, $peptideId, $peptide->getPositionStart()));
        }
    }

    private function getModMass(Peptide $peptide)
    {
        if (empty($this->modifications)) {
            return 0;
        }
        
        $acids = str_split($peptide->getSequence());
        
        $mass = 0;
        foreach ($acids as $acid) {
            if (isset($this->modifications[$acid])) {
                $mass += $this->modifications[$acid];
            }
        }
        
        return $mass;
    }

    private function processDecoys($protein)
    {
        // Generate decoy protein
        $protein->reverseSequence();
        $peptides = $this->cleaver->digest($protein);
        $protein->reverseSequence();
        
        $peptides = $this->filter->filterPeptide($peptides);
        
        foreach ($peptides as $peptide) {
            if (! isset($this->peptide2Id[$peptide->getSequence()])) {
                $peptideMass = $peptide->calculateMass();
                $modMass = $this->getModMass($peptide);
                
                $this->peptideBulk->append(
                    sprintf('(%d, %d, %s, %d, %d, %f, %f, 1)', $this->peptideId, $this->jobId, 
                        $this->adodb->quote($peptide->getSequence()), $peptide->getLength(), 
                        $peptide->getMissedCleavageCount(), $peptideMass, $peptideMass + $modMass));
                
                $this->peptide2Id[$peptide->getSequence()] = $this->peptideId;
                
                $this->peptideId ++;
            }
        }
    }
}
