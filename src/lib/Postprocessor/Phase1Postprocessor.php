<?php
/**
 * Copyright 2019 University of Liverpool
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
use pgb_liv\php_ms\Core\Protein;
use pgb_liv\php_ms\Writer\MgfWriter;
use pgb_liv\php_ms\Writer\MzIdentMlWriter;
use pgb_liv\crowdsource\Core\FragmentIon;

/**
 * Logic for performing result generation and database clean up after a job is complete
 * @todo This class needs refactoring to independent classes for each output type
 * @author Andrew Collins
 */
class Phase1Postprocessor
{

    const RESULTS_URL = 'http://pgb.liv.ac.uk/~andrew/crowdsource-server/src/public_html/results';

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

    public function resultsReady()
    {
        $record = $this->adodb->GetRow(
            'SELECT `workunits_created`, `workunits_returned` FROM `job_queue` WHERE `id` =' . $this->jobId);

        if ($record['workunits_created'] == 0) {
            return false;
        }

        if ($record['workunits_created'] != $record['workunits_returned']) {
            return false;
        }

        $this->adodb->Execute('UPDATE `job_queue` SET `process_end` = NOW() WHERE `id` = ' . $this->jobId);

        return true;
    }

    /**
     * Writes results in CSV format matching Peaks output, for ease of reading/comparison
     */
    public function generateResults()
    {
        $path = DATA_PATH . '/' . $this->jobId . '/results';

        if (! is_dir($path)) {
            mkdir($path);
        }

        $this->writeStats();
        $this->writeCsv();
        $this->writeMzIdentMl();
        $this->writeMgf();
    }

    public function clean()
    {
        $this->adodb->Execute('DELETE FROM `fasta_peptide_fixed` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `raw_ms1` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `raw_ms2` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `workunit1` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `workunit1_locations` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `analytic_meta` WHERE `job` = ' . $this->jobId);
    }

    public function finalise()
    {
        $this->adodb->Execute('UPDATE `job_queue` SET `state` = "COMPLETE" WHERE `id` = ' . $this->jobId);
    }

    private function initialiseMzIdentML(MzIdentMlWriter $mzIdentMl)
    {
        $jobRecord = $this->adodb->GetRow(
            'SELECT `f`.`id`, `raw_file`, `database_file`, `precursor_tolerance`, `precursor_tolerance_unit`, `fragment_tolerance`, `fragment_tolerance_unit`, `f`.`enzyme`, `miss_cleave_max` FROM `job_queue` `jq` LEFT JOIN `fasta` `f` ON `database_hash` = `hash` && `jq`.`enzyme` = `f`.`enzyme` WHERE `jq`.`id` = ' .
            $this->jobId);

        $targetSequenceCount = $this->adodb->GetOne(
            'SELECT COUNT(*) FROM `fasta_proteins` WHERE `fasta` = ' . $jobRecord['id']);
        $creationDate = filemtime($jobRecord['database_file']);
        
        $mzIdentMl->addCv('UNIMOD Modifications ontology', null, 'http://www.unimod.org/obo/unimod.obo', 'UNIMOD');

        $mzIdentMl->addSoftware('CS', 'CrowdSourcing', '1.0');
        $mzIdentMl->addSearchData(basename($jobRecord['database_file']), $targetSequenceCount, $creationDate,
            $creationDate);
        $mzIdentMl->addDecoyData('DECOY_' . basename($jobRecord['database_file']));
        $mzIdentMl->addFragmentTolerance(
            new Tolerance((float) $jobRecord['fragment_tolerance'], $jobRecord['precursor_tolerance_unit']));
        $mzIdentMl->addParentTolerance(
            new Tolerance((float) $jobRecord['precursor_tolerance'], $jobRecord['precursor_tolerance_unit']));

        $mzIdentMl->addScore('MS:1002352', '-10lgP');
        $mzIdentMl->addSpectraData(self::RESULTS_URL . $this->jobId . '/processed.mgf');

        $fixedMods = $this->adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_fixed_mod` WHERE `job` = ' . $this->jobId);
        $varMods = $this->adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_variable_mod` WHERE `job` = ' . $this->jobId);
        $modId2Mod = $this->adodb->GetAssoc('SELECT `record_id`, `code_name`, `mono_mass` FROM `unimod_modifications`');

        foreach ($fixedMods as $mod) {
            $modification = new Modification();
            $modification->setAccession('UNIMOD:' . $mod['mod_id']);

            if ($mod['acid'] == '[') {
                $modification->setPosition(Modification::POSITION_NTERM);
            } elseif ($mod['acid'] == ']') {
                $modification->setPosition(Modification::POSITION_CTERM);
            } else {
                $modification->setResidues(array(
                    $mod['acid']
                ));
            }

            $modification->setMonoisotopicMass((float) $modId2Mod[$mod['mod_id']]['mono_mass']);
            $modification->setType(Modification::TYPE_FIXED);
            $mzIdentMl->addModification($modification);
        }

        foreach ($varMods as $mod) {
            $modification = new Modification();
            $modification->setAccession('UNIMOD:' . $mod['mod_id']);

            if ($mod['acid'] == '[') {
                $modification->setPosition(Modification::POSITION_NTERM);
            } elseif ($mod['acid'] == ']') {
                $modification->setPosition(Modification::POSITION_CTERM);
            } else {
                $modification->setResidues(array(
                    $mod['acid']
                ));
            }

            $modification->setMonoisotopicMass((float) $modId2Mod[$mod['mod_id']]['mono_mass']);
            $mzIdentMl->addModification($modification);
        }

        $mzIdentMl->addEnzyme('MS:1001251', 'PSI-MS', $jobRecord['miss_cleave_max']);
        $mzIdentMl->open();

        $fastaId = $jobRecord['id'];

        $precursorRecords = $this->adodb->Execute('SELECT * FROM `raw_ms1` WHERE `job` = ' . $this->jobId);

        $precursorIons = array();
        $proteinMap = array();
        
        foreach ($precursorRecords as $precursorRecord) {
            $precursorIon = new PrecursorIon();
            $precursorIon->setIdentifier($precursorRecord['id']);
            $precursorIon->setMonoisotopicMassCharge((float) $precursorRecord['mass_charge'],
                (int) $precursorRecord['charge']);
            $precursorIon->setRetentionTime((float) $precursorRecord['rtinseconds']);
            $precursorIon->setTitle($precursorRecord['title']);

            $psmRecords = $this->adodb->Execute(
                'SELECT `w`.`precursor`, `w`.`peptide`, `w`.`score`, `p`.`peptide` AS `sequence`, `p`.`is_decoy` FROM `workunit1` `w` 
LEFT JOIN `fasta_peptides` `p` ON `fasta` = ' . $fastaId . ' && `p`.`id` = `w`.`peptide` 
WHERE `w`.`job` = ' . $this->jobId . ' && `precursor` = ' . $precursorRecord['id'] . ' ORDER BY `score` DESC LIMIT 0,' .
                self::PSM_LIMIT);

            $rank = 1;
            foreach ($psmRecords as $psmRecord) {
                $identification = new Identification();
                $identification->setRank($rank);
                $identification->setScore('-10lgP', $psmRecord['score']);

                $peptide = new Peptide();
                $peptide->setSequence($psmRecord['sequence']);
                $peptide->setIsDecoy($psmRecord['is_decoy'] == '1');

                $proteinRecords = $this->adodb->GetAll(
                    'SELECT DISTINCT `identifier`, `description`, `sequence`, `position_start` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_proteins` `p` ON `p2p`.`protein` = `p`.`id` && `p2p`.`fasta` = `p`.`fasta` WHERE `p2p`.`fasta` = ' .
                    $fastaId . ' AND `peptide` = ' . $psmRecord['peptide']);

                foreach ($proteinRecords as $proteinRecord) {
                    $uid = '';
                    if ($peptide->isDecoy()) {
                        $uid .= 'DECOY_';
                    }
                    
                    $uid .= $proteinRecord['identifier'];
                    
                    if (! isset($proteinMap[$uid])) {
                        $protein = new Protein();
                        $protein->setDescription($proteinRecord['description']);
                        $protein->setSequence($proteinRecord['sequence']);
                        
                        if ($peptide->isDecoy()) {
                            $protein->reverseSequence();
                        }
                        
                        $protein->setUniqueIdentifier($uid);
                        $protein->setAccession($uid);
                        
                        $proteinMap[$uid] = $protein;
                    }
                    
                    $protein = $proteinMap[$uid];
                    
                    $peptide->addProtein($protein, $proteinRecord['position_start'] + 1, $proteinRecord['position_start'] + $peptide->getLength() + 1);
                }

                $ptmRecords = $this->adodb->GetAll(
                    'SELECT `location`, `modification` FROM `workunit1_locations` WHERE `job` = ' . $this->jobId .
                    ' && `precursor` = ' . $psmRecord['precursor'] . ' && `peptide` = ' . $psmRecord['peptide']);

                foreach ($ptmRecords as $ptmRecord) {
                    $modId = $ptmRecord['modification'];

                    $modification = new Modification();
                    $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                    $modification->setLocation((int) $ptmRecord['location']);
                    $modification->setAccession('UNIMOD:' . $modId);

                    $peptide->addModification($modification);
                }

                foreach ($fixedMods as $fixedMod) {
                    $matches = array();
                    if (preg_match_all('/' . $fixedMod['acid'] . '/', $peptide->getSequence(), $matches,
                        PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[0] as $match) {
                            $modId = $fixedMod['mod_id'];

                            $modification = new Modification();
                            $modification->setMonoisotopicMass((float) $modId2Mod[$modId]['mono_mass']);
                            $modification->setLocation((int) $match[1] + 1);
                            $modification->setAccession('UNIMOD:' . $modId);

                            $peptide->addModification($modification);
                        }
                    }
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
        
        return $precursorIons;
    }

    private function writeMzIdentMl()
    {
        echo '[' . date('r') . '] Writing mzIdentML....' . PHP_EOL;
        $path = DATA_PATH . '/' . $this->jobId . '/results/results.mzid';
        $mzIdentMl = new MzIdentMlWriter($path);
        $precursorIons = $this->initialiseMzIdentML($mzIdentMl);
        
        echo '[' . date('r') . '] Data prepared.' . PHP_EOL;
        $mzIdentMl->addIdentifiedPrecursors($precursorIons);
        
        echo '[' . date('r') . '] MzIdentML written.' . PHP_EOL;
        $mzIdentMl->close();
    }

    private function writeCsv()
    {
        echo '[' . date('r') . '] Writing CSV.' . PHP_EOL;
        
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
            $score['precursor'] = $record['precursor'];
            $score['ions_matched'] = $record['ions_matched'];
            $scores[$record['precursor']] = $score;
        }

        $fixedMods = $this->adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_fixed_mod` WHERE `job` = ' . $this->jobId);
        $modId2Mod = $this->adodb->GetAssoc('SELECT `record_id`, `code_name`, `mono_mass` FROM `unimod_modifications`');

        $fileHandle = fopen(DATA_PATH . '/' . $this->jobId . '/results/results.csv', 'w');
        fwrite($fileHandle,
            'Peptide,-10lgP,Mass,Length,ppm,m/z,RT,Intensity,Fraction,Scan,Source File,Accession,PTM,AScore,IonMatches' .
            PHP_EOL);

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
                ' && `precursor` = ' . $score['precursor'] . ' && `peptide` = ' . $score['peptide']);

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
            $identification->setIonsMatched((int) $score['ions_matched']);

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
            fwrite($fileHandle, 0 . ',');
            fwrite($fileHandle, $identification->getIonsMatched());
            fwrite($fileHandle, PHP_EOL);
        }
        
        echo '[' . date('r') . '] CSV written.' . PHP_EOL;
    }

    private function writeMgf()
    {
        echo '[' . date('r') . '] Writing MGF.' . PHP_EOL;
        $mgfWriter = new MgfWriter(DATA_PATH . '/' . $this->jobId . '/results/processed.mgf');

        $precursorRecords = $this->adodb->Execute('SELECT * FROM `raw_ms1` WHERE `job` = ' . $this->jobId);

        foreach ($precursorRecords as $precursorRecord) {
            $precursorIon = new PrecursorIon();
            $precursorIon->setMonoisotopicMassCharge((float) $precursorRecord['mass_charge'],
                (int) $precursorRecord['charge']);
            $precursorIon->setScan((int) $precursorRecord['scans']);
            $precursorIon->setTitle($precursorRecord['title']);
            $precursorIon->setRetentionTime((float) $precursorRecord['rtinseconds']);

            $fragmentRecords = $this->adodb->Execute(
                'SELECT * FROM `raw_ms2` WHERE `job` = ' . $this->jobId . ' && `ms1` = ' . $precursorRecord['id'] .
                ' ORDER BY `mz` ASC');

            foreach ($fragmentRecords as $fragmentRecord) {
                $fragmentIon = new FragmentIon((float) $fragmentRecord['mz'], (float) $fragmentRecord['intensity']);

                $precursorIon->addFragmentIon($fragmentIon);
            }

            $mgfWriter->write($precursorIon);
        }

        $mgfWriter->close();
        echo '[' . date('r') . '] MzIdentML written.' . PHP_EOL;
    }

    private function writeStats()
    {
        echo '[' . date('r') . '] Writing stats.' . PHP_EOL;
        // Write users
        $data = $this->adodb->GetAll(
            'SELECT `ip`,  COUNT(*) AS `workunits`, SUM(`sent`) AS `bytes_sent`, AVG(`sent`) AS `bytes_sent_avg`, SUM(`received`) AS `bytes_received`, AVG(`received`) AS `bytes_received_avg`, SUM(`cpu`) AS `cpu_total`, MAX(`cpu`) AS `cpu_max`, MIN(`cpu`) AS `cpu_min` FROM `analytic_meta` WHERE `job` = ' .
            $this->jobId . ' GROUP BY `ip`');

        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents(DATA_PATH . '/' . $this->jobId . '/results/user.json', $json);

        // Write hosts
        $data = $this->adodb->GetAll(
            'SELECT `host`,  COUNT(*) AS `workunits`, SUM(`sent`) AS `bytes_sent`, SUM(`received`) AS `bytes_received`, SUM(`cpu`) AS `cpu_total`, MAX(`cpu`) AS `cpu_max`, MIN(`cpu`) AS `cpu_min` FROM `analytic_meta` WHERE `job` = ' .
            $this->jobId . ' GROUP BY `host`');
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents(DATA_PATH . '/' . $this->jobId . '/results/host.json', $json);

        // Write precursors
        $data = $this->adodb->GetAll(
            'SELECT SUBSTRING(SUBSTRING_INDEX(`uid`, "-", 2), LOCATE("-", `uid`)+1) AS `precursor`, COUNT(*) AS `workunits`, SUM(`sent`) AS `bytes_sent`, AVG(`sent`) AS `bytes_sent_avg`, SUM(`received`) AS `bytes_received`, AVG(`received`) AS `bytes_received_avg`, SUM(`cpu`) AS `cpu_total`, MAX(`cpu`) AS `cpu_max`, MIN(`cpu`) AS `cpu_min` FROM `analytic_meta` WHERE `job` = ' .
            $this->jobId . ' GROUP BY `precursor` ');
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents(DATA_PATH . '/' . $this->jobId . '/results/precursor.json', $json);
        echo '[' . date('r') . '] Stats written.' . PHP_EOL;
    }
}
