<?php
/**
 * Copyright 2018 University of Liverpool
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
namespace pgb_liv\crowdsource\Postprocessor;

use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Core\Identification;
use pgb_liv\php_ms\Core\Spectra\PrecursorIon;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\crowdsource\MzIdentMlWriter;
use pgb_liv\php_ms\Core\Protein;

/**
 * Logic for performing result generation and database clean up after a job is complete
 *
 * @author Andrew Collins
 */
class Phase1Postprocessor
{

    const PSM_LIMIT = 5;

    protected $adodb;

    protected $jobId;

    /**
     * Creates a new instance of a preprocessor.
     *
     * @param \ADOConnection $conn
     *            A valid and connected ADOdb instance
     * @param int $jobId
     *            The job to preprocess
     * @throws \InvalidArgumentException If job is not an integer
     */
    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }

        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    /**
     * Writes results in CSV format matching Peaks output, for ease of reading/comparison
     *
     * @todo Update to write as mzIdentML
     */
    public function generateResults()
    {
        $this->writeCsv();
        $this->writeMzIdentMl();
    }

    public function clean()
    {
        return;

        $this->adodb->Execute('DELETE FROM `fasta_peptide_fixed` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `raw_ms1` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `raw_ms2` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `workunit1` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `workunit1_locations` WHERE `job` = ' . $this->jobId);
    }

    public function finalise()
    {
        // $this->adodb->Execute('UPDATE `job_queue` SET `state` = "COMPLETE" WHERE `id` = ' . $this->jobId);
    }

    private function writeMzIdentMl()
    {
        $jobRecord = $this->adodb->GetRow(
            'SELECT `f`.`id`, `raw_file`, `database_file`, `precursor_tolerance`, `precursor_tolerance_unit`, `fragment_tolerance`, `fragment_tolerance_unit`, `f`.`enzyme`, `miss_cleave_max` FROM `job_queue` `jq` LEFT JOIN `fasta` `f` ON `database_hash` = `hash` && `jq`.`enzyme` = `f`.`enzyme` WHERE `jq`.`id` = ' .
            $this->jobId);

        $path = DATA_PATH . '/' . $this->jobId . '/results.mzid';

        $mzIdentMl = new MzIdentMlWriter($path);
        $mzIdentMl->addCv('UNIMOD Modifications ontology', null, 'http://www.unimod.org/obo/unimod.obo', 'UNIMOD');

        $mzIdentMl->addSoftware('CS', 'CrowdSourcing', '1.0');
        $mzIdentMl->addDecoyData(basename($jobRecord['database_file']));
        $mzIdentMl->addSearchData(basename($jobRecord['database_file']));
        $mzIdentMl->addFragmentTolerance(
            new Tolerance((float) $jobRecord['fragment_tolerance'], $jobRecord['precursor_tolerance_unit']));
        $mzIdentMl->addParentTolerance(
            new Tolerance((float) $jobRecord['precursor_tolerance'], $jobRecord['precursor_tolerance_unit']));

        $mzIdentMl->addScore('MS:1001870', '-10lgP');
        $mzIdentMl->addSpectraData(basename($jobRecord['raw_file']));

        $fixedMods = $this->adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_fixed_mod` WHERE `job` = ' . $this->jobId);
        $varMods = $this->adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_variable_mod` WHERE `job` = ' . $this->jobId);
        $modId2Mod = $this->adodb->GetAssoc('SELECT `record_id`, `code_name`, `mono_mass` FROM `unimod_modifications`');

        foreach ($fixedMods as $mod) {
            $modification = new Modification();
            $modification->setAccession('UNIMOD:' . $mod['mod_id']);
            $modification->setResidues(array(
                $mod['acid']
            ));
            $modification->setMonoisotopicMass((float) $modId2Mod[$mod['mod_id']]['mono_mass']);
            $mzIdentMl->addModification($modification);
        }

        foreach ($varMods as $mod) {
            $modification = new Modification();
            $modification->setAccession('UNIMOD:' . $mod['mod_id']);
            $modification->setResidues(array(
                $mod['acid']
            ));
            $modification->setMonoisotopicMass((float) $modId2Mod[$mod['mod_id']]['mono_mass']);
            $mzIdentMl->addModification($modification);
        }

        $mzIdentMl->addEnzyme('MS:1001251', 'PSI-MS', $jobRecord['miss_cleave_max']);
        $mzIdentMl->open();

        $fastaId = $jobRecord['id'];

        $precursorRecords = $this->adodb->Execute('SELECT * FROM `raw_ms1` WHERE `job` = ' . $this->jobId);

        $precursorIons = array();
        foreach ($precursorRecords as $precursorRecord) {
            $precursorIon = new PrecursorIon();
            $precursorIon->setMonoisotopicMassCharge((float) $precursorRecord['mass_charge'],
                (int) $precursorRecord['charge']);
            $precursorIon->setRetentionTime((float) $precursorRecord['rtinseconds']);
            $precursorIon->setTitle($precursorRecord['title']);

            $psmRecords = $this->adodb->Execute(
                'SELECT * FROM `workunit1` WHERE `job` = ' . $this->jobId . ' && `precursor` = ' . $precursorRecord['id'] .
                ' && `score` > 0 ORDER BY `score` DESC LIMIT 0,' . self::PSM_LIMIT);

            $rank = 1;
            foreach ($psmRecords as $psmRecord) {
                $identification = new Identification();
                $identification->setRank($rank);
                $identification->setScore('-10lgP', $psmRecord['score']);

                $peptideRecord = $this->adodb->GetRow(
                    'SELECT `peptide`, `is_decoy` FROM `fasta_peptides` WHERE `fasta` = ' . $fastaId . ' && `id` = ' .
                    $psmRecord['peptide']);

                $peptide = new Peptide();
                $peptide->setSequence($peptideRecord['peptide']);
                $peptide->setIsDecoy($peptideRecord['is_decoy'] == '1' ? true : false);

                $proteinRecords = $this->adodb->GetAll(
                    'SELECT DISTINCT `identifier`, `description`, `sequence` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_proteins` `p` ON `p2p`.`protein` = `p`.`id` && `p2p`.`fasta` = `p`.`fasta` WHERE `p2p`.`fasta` = ' .
                    $fastaId . ' AND `peptide` = ' . $psmRecord['peptide']);

                foreach ($proteinRecords as $proteinRecord) {
                    // TODO: Memory can be reduced here if needed
                    $protein = new Protein();
                    $uid = '';
                    
                    $protein->setDescription($proteinRecord['description']);
                    $protein->setSequence($proteinRecord['sequence']);
                    
                    if ($peptide->isDecoy()) {
                        $uid = 'DECOY_';
                        $protein->reverseSequence();
                    }

                    $uid .= $proteinRecord['identifier'];
                    $protein->setUniqueIdentifier($uid);
                    $protein->setAccession($uid);

                    $peptide->addProtein($protein);
                }

                $ptmRecords = $this->adodb->GetAll(
                    'SELECT `location`, `modification` FROM `workunit1_locations` WHERE `job` = ' . $this->jobId .
                    ' && `id` = ' . $psmRecord['id']);

                foreach ($ptmRecords as $ptmRecord) {
                    $modId = $ptmRecord['modification'];

                    $modification = new Modification();
                    $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                    $modification->setLocation((int) $ptmRecord['location']);
                    $modification->setAccession('UNIMOD:' . $modId);

                    $peptide->addModification($modification);
                }

                $identification->setSequence($peptide);
                $precursorIon->addIdentification($identification);

                $rank ++;
            }

            if (count($precursorIon->getIdentifications()) == 0) {
                continue;
            }

            $precursorIons[] = $precursorIon;
        }

        $mzIdentMl->addIdentifiedPrecursors($precursorIons);

        $mzIdentMl->close();

        exit();

        $records = $this->adodb->Execute(
            'SELECT * FROM `workunit1` WHERE `job` = ' . $this->jobId .
            ' && `score` IS NOT NULL && `score` > 0 ORDER BY `precursor` ASC, `score` DESC');
        $scores = array();

        foreach ($records as $record) {
            if (isset($scores[$record['precursor']]) && count($scores[$record['precursor']] >= 10)) {
                continue;
            }

            $score = array();
            $score['score'] = $record['score'];
            $score['peptide'] = $record['peptide'];
            $score['id'] = $record['id'];
            $scores[$record['precursor']] = $score;
        }

        $fileHandle = fopen(DATA_PATH . '/' . $this->jobId . '/results.csv', 'w');
        fwrite($fileHandle,
            'Peptide,-10lgP,Mass,Length,ppm,m/z,RT,Intensity,Fraction,Scan,Source File,Accession,PTM,AScore' . PHP_EOL);

        $precursors = array();
        foreach ($scores as $id => $score) {
            $peptideRecord = $this->adodb->GetRow(
                'SELECT `peptide`, `is_decoy` FROM `fasta_peptides` WHERE `fasta` = ' . $fastaId . ' && `id` = ' .
                $score['peptide']);

            $precursorRecord = $this->adodb->GetRow(
                'SELECT * FROM `raw_ms1` WHERE `job` = ' . $this->jobId . ' && `id` = ' . $id);

            $proteinRecords = $this->adodb->GetCol(
                'SELECT DISTINCT `identifier` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_proteins` `p` ON `p2p`.`protein` = `p`.`id` && `p2p`.`fasta` = `p`.`fasta` WHERE `p2p`.`fasta` = ' .
                $fastaId . ' AND `peptide` = ' . $score['peptide']);

            $ptmRecords = $this->adodb->GetAll(
                'SELECT `location`, `modification` FROM `workunit1_locations` WHERE `job` = ' . $this->jobId .
                ' && `id` = ' . $score['id']);

            $accession = 'DECOY';
            if (! empty($proteinRecords)) {
                $accession = implode(':', $proteinRecords);
            }

            $intensity = 0;
            $matches = array();
            if (preg_match('/intensity=([0-9.]+)/', $precursorRecord['title'], $matches)) {
                $intensity = $matches[1];
            }

            $peptide = new Peptide();
            $peptide->setSequence($peptideRecord['peptide']);

            $protein = new Protein();
            $protein->setUniqueIdentifier('MY_BIG_PROTEIN');
            $protein->setAccession('BIG_PROT');
            $protein->setSequence('PEPTIDER');
            $protein->setDescription('Huge protein');
            $peptide->addProtein($protein, 0, 10);

            $matches = null;
            foreach ($fixedMods as $fixedMod) {
                if (preg_match_all('/' . $fixedMod['acid'] . '/', $peptide->getSequence(), $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $modId = $fixedMod['mod_id'];

                        $modification = new Modification();
                        $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                        $modification->setName($modId2Mod[$modId]['code_name']);
                        $modification->setLocation((int) $match[1] + 1);
                        $modification->setAccession('UNIMOD:' . $modId);

                        $peptide->addModification($modification);
                    }
                }
            }

            foreach ($ptmRecords as $ptm) {
                $modId = $ptm['modification'];

                $modification = new Modification();
                $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                $modification->setName($modId2Mod[$modId]['code_name']);
                $modification->setLocation((int) $ptm['location']);
                $modification->setAccession('UNIMOD:' . $modId);

                $peptide->addModification($modification);
            }

            $identification = new Identification();
            $identification->setSequence($peptide);
            $identification->setScore('-10lgP', $score['score']);
            $identification->setRank(1); // TODO:

            $precursor = new PrecursorIon();
            $precursor->setMonoisotopicMassCharge((float) $precursorRecord['mass_charge'],
                (int) $precursorRecord['charge']);
            $precursor->setRetentionTime((float) $precursorRecord['rtinseconds']);
            $precursor->setIntensity((float) $intensity);
            $precursor->setScan((int) $precursorRecord['scans']);
            $precursor->setTitle($precursorRecord['title']);

            $precursor->addIdentification($identification);

            $precursors[] = $precursor;
        }

        $mzIdentMl->addIdentifiedPrecursors($precursors);

        $mzIdentMl->close();
    }

    private function writeCsv()
    {
        $jobRecord = $this->adodb->GetRow(
            'SELECT `f`.`id`, `raw_file` FROM `job_queue` `jq` LEFT JOIN `fasta` `f` ON `database_hash` = `hash` && `jq`.`enzyme` = `f`.`enzyme` WHERE `jq`.`id` = ' .
            $this->jobId);
        $fastaId = $jobRecord['id'];

        $records = $this->adodb->Execute(
            'SELECT * FROM `workunit1` WHERE `job` = ' . $this->jobId .
            ' && `score` IS NOT NULL && `score` > 0 ORDER BY `precursor` ASC, `score` DESC');
        $scores = array();

        foreach ($records as $record) {
            if (isset($scores[$record['precursor']])) {
                continue;
            }

            $score = array();
            $score['score'] = $record['score'];
            $score['peptide'] = $record['peptide'];
            $score['id'] = $record['id'];
            $scores[$record['precursor']] = $score;
        }

        $fixedMods = $this->adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_fixed_mod` WHERE `job` = ' . $this->jobId);
        $modId2Mod = $this->adodb->GetAssoc('SELECT `record_id`, `code_name`, `mono_mass` FROM `unimod_modifications`');

        $fileHandle = fopen(DATA_PATH . '/' . $this->jobId . '/results.csv', 'w');
        fwrite($fileHandle,
            'Peptide,-10lgP,Mass,Length,ppm,m/z,RT,Intensity,Fraction,Scan,Source File,Accession,PTM,AScore' . PHP_EOL);

        foreach ($scores as $id => $score) {
            $peptideRecord = $this->adodb->GetRow(
                'SELECT `peptide`, `is_decoy` FROM `fasta_peptides` WHERE `fasta` = ' . $fastaId . ' && `id` = ' .
                $score['peptide']);

            $precursorRecord = $this->adodb->GetRow(
                'SELECT * FROM `raw_ms1` WHERE `job` = ' . $this->jobId . ' && `id` = ' . $id);

            $proteinRecords = $this->adodb->GetCol(
                'SELECT DISTINCT `identifier` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_proteins` `p` ON `p2p`.`protein` = `p`.`id` && `p2p`.`fasta` = `p`.`fasta` WHERE `p2p`.`fasta` = ' .
                $fastaId . ' AND `peptide` = ' . $score['peptide']);

            $ptmRecords = $this->adodb->GetAll(
                'SELECT `location`, `modification` FROM `workunit1_locations` WHERE `job` = ' . $this->jobId .
                ' && `id` = ' . $score['id']);

            $accession = 'DECOY';
            if (! empty($proteinRecords)) {
                $accession = implode(':', $proteinRecords);
            }

            $intensity = 0;
            $matches = array();
            if (preg_match('/intensity=([0-9.]+)/', $precursorRecord['title'], $matches)) {
                $intensity = $matches[1];
            }

            $peptide = new Peptide();
            $peptide->setSequence($peptideRecord['peptide']);

            $ptmArray = array();
            $matches = null;
            foreach ($fixedMods as $fixedMod) {
                if (preg_match_all('/' . $fixedMod['acid'] . '/', $peptide->getSequence(), $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $modId = $fixedMod['mod_id'];

                        $modification = new Modification();
                        $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                        $modification->setName($modId2Mod[$modId]['code_name']);
                        $modification->setLocation((int) $match[1] + 1);

                        $peptide->addModification($modification);
                        $ptmArray[] = $modId2Mod[$modId]['code_name'];
                    }
                }
            }

            foreach ($ptmRecords as $ptm) {
                $modId = $ptm['modification'];

                $modification = new Modification();
                $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                $modification->setName($modId2Mod[$modId]['code_name']);
                $modification->setLocation((int) $ptm['location']);

                $peptide->addModification($modification);
                $ptmArray[] = $modId2Mod[$modId]['code_name'];
            }

            $ptms = implode(';', $ptmArray);

            $identification = new Identification();
            $identification->setSequence($peptide);
            $identification->setScore('-10lgP', $score['score']);

            $precursor = new PrecursorIon();
            $precursor->setMonoisotopicMassCharge((float) $precursorRecord['mass_charge'],
                (int) $precursorRecord['charge']);
            $precursor->setRetentionTime((float) $precursorRecord['rtinseconds']);
            $precursor->setIntensity((float) $intensity);
            $precursor->setScan((int) $precursorRecord['scans']);

            $precursor->addIdentification($identification);

            $ppm = Tolerance::getDifferencePpm($precursor->getMonoisotopicMass(), $peptide->getMonoisotopicMass());

            fwrite($fileHandle, $peptide->getSequence() . ',');
            fwrite($fileHandle, $identification->getScore('-10lgP') . ',');
            fwrite($fileHandle, $precursor->getMonoisotopicMass() . ',');
            fwrite($fileHandle, $peptide->getLength() . ',');
            fwrite($fileHandle, $ppm . ',');
            fwrite($fileHandle, $precursor->getMonoisotopicMassCharge() . ',');
            fwrite($fileHandle, $precursor->getRetentionTime() . ',');
            fwrite($fileHandle, $precursor->getIntensity() . ',');
            fwrite($fileHandle, 1 . ','); // fraction
            fwrite($fileHandle, $precursor->getScan() . ',');
            fwrite($fileHandle, basename($jobRecord['raw_file']) . ',');
            fwrite($fileHandle, $accession . ',');
            fwrite($fileHandle, $ptms . ',');
            fwrite($fileHandle, 0);
            fwrite($fileHandle, PHP_EOL);
        }
    }
}