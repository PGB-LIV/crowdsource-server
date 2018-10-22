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
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\php_ms\Utility\Digest\DigestFactory;

/**
 * Logic for performing all phase 1 preprocessing
 *
 * @author Andrew Collins
 */
class Phase1Preprocessor extends AbstractPreprocessor
{

    const HASH_ALGO = 'sha256';

    private $databasePath;

    private $rawPath;

    private $maxMissedCleavage;

    /**
     * Marks the preprocessing stage this phase as preparing
     */
    protected function initialise($phase)
    {
        parent::initialise($phase);

        $job = $this->adodb->GetRow(
            'SELECT `database_file`, `raw_file`, `miss_cleave_max` FROM `job_queue` WHERE `id` = ' . $this->jobId);

        $this->databasePath = $job['database_file'];
        $this->maxMissedCleavage = $job['miss_cleave_max'];
        $this->rawPath = $job['raw_file'];
    }

    /**
     * Starts the indexing all phase 1 data
     */
    public function process()
    {
        $this->initialise(1);

        echo 'Pre-processing database: ' . $this->databasePath . PHP_EOL;
        $this->indexDatabase();

        echo 'Pre-processing raw data: ' . $this->rawPath . PHP_EOL;
        $this->indexRaw();

        echo 'Pre-processing work units.' . PHP_EOL;
        $this->indexWorkUnits();

        $this->finalise();
    }

    /**
     * Creates an index of all protein database file data
     */
    private function indexDatabase()
    {
        $hash = hash_file(static::HASH_ALGO, $this->databasePath);
        $fastaId = $this->adodb->GetOne('SELECT `id` FROM `fasta` WHERE `hash` = UNHEX("' . $hash . '")');

        // TODO: Should verify indexing complete?
        if (is_null($fastaId)) {
            // Index FASTA
            $enzymeId = 1; // TODO
            $this->adodb->Execute(
                'INSERT INTO `fasta` (`enzyme`, `hash`) VALUES (' . $enzymeId . ', UNHEX("' . $hash . '"))');
            $fastaId = $this->adodb->insert_Id();
            $fastaParser = new FastaReader($this->databasePath);

            $cleaver = DigestFactory::getDigest('Trypsin'); // $job['enzyme']);

            $databaseProcessor = new DatabasePreprocessor($this->adodb, $fastaParser, $fastaId, $cleaver);
            $databaseProcessor->process();
        }

        $this->adodb->Execute(
            'UPDATE `job_queue` SET `database_hash` = UNHEX("' . $hash . '") WHERE `id` = ' . $this->jobId);

        // TODO: Generate fixed mod table
        $modifications = $this->adodb->GetAssoc(
            'SELECT `j`.`acid`, `mono_mass` FROM `job_fixed_mod` `j` LEFT JOIN `unimod_modifications` ON `j`.`mod_id` = `record_id` WHERE `j`.`job` = ' .
            $this->jobId);

        // Remove any data from a previous run
        $this->adodb->Execute('DELETE FROM `fasta_peptide_fixed` WHERE `job` = ' . $this->jobId);

        // TODO: fill fixed mod with peptides that meet requirements
        $this->adodb->Execute(
            'INSERT INTO `fasta_peptide_fixed` SELECT ' . $this->jobId .
            ', `id`, `mass` FROM `fasta_peptides` WHERE `fasta` = "' . $fastaId . '" && `missed_cleavage` <= ' .
            $this->maxMissedCleavage);

        // For each fixed mod add mass to peptides
        foreach ($modifications as $acid => $mass) {
            $this->adodb->Execute(
                'UPDATE `fasta_peptide_fixed` `f` LEFT JOIN `fasta_peptides` `p` ON `f`.`peptide` = `p`.`id` && `fasta` = ' .
                $fastaId . '
SET `fixed_mass`=`fixed_mass` + ((`length` - LENGTH(REPLACE(`p`.`peptide`, "' . $acid . '", ""))) * ' . $mass .
                ') WHERE `length` - LENGTH(REPLACE(`p`.`peptide`, "' . $acid . '", "")) > 0 && `job` = ' . $this->jobId);
        }
    }

    /**
     * Creates an index of all raw file data
     */
    private function indexRaw()
    {
        $mgfParser = new MgfReader($this->rawPath);

        $rawProcessor = new RawPreprocessor($this->adodb, $mgfParser, $this->jobId);
        $rawProcessor->setMs2PeakCount(MS2_PEAK_LIMIT, MS2_PEAK_WINDOW);
        $rawProcessor->process();
    }

    /**
     * Creates an index of all phase 1 work units
     */
    private function indexWorkUnits()
    {
        $workProcessor = new WorkUnitPreprocessor($this->adodb, $this->jobId);
        $workProcessor->process();
    }
}
