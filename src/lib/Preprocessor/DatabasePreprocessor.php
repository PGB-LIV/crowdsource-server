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
use pgb_liv\php_ms\Utility\Filter\FilterLength;
use pgb_liv\crowdsource\BulkQuery;
use pgb_liv\php_ms\Utility\Digest\DigestInterface;

class DatabasePreprocessor
{
    const maxMissedCleaves = 2;

    private $peptide2Id = array();

    private $adodb;

    private $databaseParser;

    private $fastaId;

    private $cleaver;

    private $filter;

    private $peptideId = 1;

    private $proteinId = 1;

    private $proteinBulk;

    private $peptideBulk;

    private $protein2peptideBulk;

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn
     *            Open ADOdb connection to database to write to
     * @param FastaReader $databaseParser
     *            Open FastaReader instance to read from
     * @param int $fastaId
     *            The fasta to process
     */
    public function __construct(\ADOConnection $conn, FastaReader $databaseParser, $fastaId, DigestInterface $cleaver)
    {
        if (! is_int($fastaId)) {
            throw new \InvalidArgumentException(
                'Fasta ID must be an integer value. Valued passed is of type ' . gettype($fastaId));
        }

        $this->adodb = $conn;
        $this->databaseParser = $databaseParser;
        $this->fastaId = $fastaId;
        $this->cleaver = $cleaver;
    }

    private function initialise()
    {
        $this->filter = new FilterLength(5, 60);
        $this->cleaver->setMaxMissedCleavage(static::maxMissedCleaves);

        $this->proteinBulk = new BulkQuery($this->adodb,
            'INSERT IGNORE INTO `fasta_proteins` (`fasta`, `id`, `identifier`, `description`, `sequence`) VALUES ');
        $this->peptideBulk = new BulkQuery($this->adodb,
            'INSERT IGNORE INTO `fasta_peptides` (`fasta`, `id`, `peptide`, `length`, `missed_cleavage`, `mass`, `is_decoy`) VALUES');
        $this->protein2peptideBulk = new BulkQuery($this->adodb,
            'INSERT INTO `fasta_protein2peptide` (`fasta`, `protein`, `peptide`, `position_start`) VALUES ');
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
        $rs = $this->adodb->Execute('SELECT `id`, `sequence` FROM `fasta_proteins` WHERE `fasta` = ' . $this->fastaId);
        foreach ($rs as $record) {
            $protein = new Protein();
            $protein->setSequence($record['sequence']);
            $this->processPeptides($record['id'], $protein);
        }

        $this->protein2peptideBulk->close();

        // Third pass. Insert decoys into database.
        $rs = $this->adodb->Execute('SELECT `id`, `sequence` FROM `fasta_proteins` WHERE `fasta` = ' . $this->fastaId);
        foreach ($rs as $record) {
            $protein = new Protein();
            $protein->setSequence($record['sequence']);
            $this->processDecoys($protein);
        }

        $this->peptideBulk->close();
        
        // Fourth Pass. Index residues
        $alphabet = range('A', 'Z');
        foreach ($alphabet as $letter) {
            $this->adodb->Execute(
                'UPDATE `fasta_peptides` SET `' . strtolower($letter) . '_count`= `length` - LENGTH(REPLACE(`peptide`, "' .
                $letter . '", "")) WHERE `fasta` = '. $this->fastaId);
        }
        
        $this->adodb->Execute('UPDATE `fasta` SET `is_indexed` = 1 WHERE `id` = '. $this->fastaId);
        
        // Clear memory
        $this->peptide2Id = array();
    }

    private function processProtein(Protein $protein)
    {
        $this->proteinBulk->append(
            sprintf('(%d, %d, %s, %s, %s)', $this->fastaId, $this->proteinId,
                $this->adodb->quote($protein->getUniqueIdentifier()), $this->adodb->quote($protein->getDescription()),
                $this->adodb->quote($protein->getSequence())));

        $this->proteinId ++;
    }

    private function processPeptides($proteinId, Protein $protein)
    {
        $peptides = $this->cleaver->digest($protein);
        $peptides = $this->filter->filterPeptide($peptides);

        foreach ($peptides as $peptide) {
            if (! isset($this->peptide2Id[$peptide->getSequence()])) {
                $peptideMass = $peptide->getMass();

                $this->peptideBulk->append(
                    sprintf('(%d, %d, %s, %d, %d, %f, 0)', $this->fastaId, $this->peptideId,
                        $this->adodb->quote($peptide->getSequence()), $peptide->getLength(),
                        $peptide->getMissedCleavageCount(), $peptideMass));

                $this->peptide2Id[$peptide->getSequence()] = $this->peptideId;

                $this->peptideId ++;
            }

            $peptideId = $this->peptide2Id[$peptide->getSequence()];

            foreach ($peptide->getProteins() as $proteinEntry) {
                $this->protein2peptideBulk->append(
                    sprintf('(%d, %d, %d, %d)', $this->fastaId, $proteinId, $peptideId, $proteinEntry->getStart()));
            }
        }
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
                $peptideMass = $peptide->getMass();

                $this->peptideBulk->append(
                    sprintf('(%d, %d, %s, %d, %d, %f, 1)', $this->fastaId, $this->peptideId,
                        $this->adodb->quote($peptide->getSequence()), $peptide->getLength(),
                        $peptide->getMissedCleavageCount(), $peptideMass));

                $this->peptide2Id[$peptide->getSequence()] = $this->peptideId;

                $this->peptideId ++;
            }
        }
    }
}
