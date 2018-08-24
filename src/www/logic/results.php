<?php
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Core\Spectra\PrecursorIon;
use pgb_liv\php_ms\Core\Identification;
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Core\Modification;

header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', true);

$jobId = 1;

if (isset($_GET['job'])) {
    $jobId = $_GET['job'];
}

$jobRecord = $adodb->GetRow(
    'SELECT `f`.`id`, `raw_file` FROM `job_queue` `jq` LEFT JOIN `fasta` `f` ON `database_hash` = `hash` && `jq`.`enzyme` = `f`.`enzyme` WHERE `jq`.`id` = ' .
    $jobId);
$fastaId = $jobRecord['id'];

$records = $adodb->Execute(
    'SELECT * FROM `workunit1` WHERE `job` = ' . $jobId .
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

$fixedMods = $adodb->GetAll('SELECT `mod_id`, `acid` FROM `job_fixed_mod` WHERE `job` = ' . $jobId);
$modId2Mod = $adodb->GetAssoc('SELECT `record_id`, `code_name`, `mono_mass` FROM `unimod_modifications`');

echo 'Peptide,-10lgP,Mass,Length,ppm,m/z,RT,Intensity,Fraction,Scan,Source File,Accession,PTM,AScore' . PHP_EOL;
foreach ($scores as $id => $score) {
    $peptideRecord = $adodb->GetRow(
        'SELECT `peptide`, `is_decoy` FROM `fasta_peptides` WHERE `fasta` = ' . $fastaId . ' && `id` = ' .
        $score['peptide']);

    $precursorRecord = $adodb->GetRow('SELECT * FROM `raw_ms1` WHERE `job` = '.$jobId.' && `id` = ' . $id);

    $proteinRecords = $adodb->GetCol(
        'SELECT DISTINCT `identifier` FROM `fasta_protein2peptide` `p2p` LEFT JOIN `fasta_proteins` `p` ON `p2p`.`protein` = `p`.`id` && `p2p`.`fasta` = `p`.`fasta` WHERE `p2p`.`fasta` = ' .
        $fastaId . ' AND `peptide` = ' . $score['peptide']);

    $ptmRecords = $adodb->GetAll(
        'SELECT `location`, `modification` FROM `workunit1_locations` WHERE `job` = ' . $jobId . ' && `id` = ' .
        $score['id']);

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
    $precursor->setMonoisotopicMassCharge((float) $precursorRecord['mass_charge'], (int) $precursorRecord['charge']);
    $precursor->setRetentionTime((float) $precursorRecord['rtinseconds']);
    $precursor->setIntensity((float) $intensity);
    $precursor->setScan((int) $precursorRecord['scans']);

    $precursor->addIdentification($identification);

    $ppm = Tolerance::getDifferencePpm($precursor->getMonoisotopicMass(), $peptide->getMonoisotopicMass());

    echo $peptide->getSequence() . ',';
    echo $identification->getScore('-10lgP') . ',';
    echo $precursor->getMonoisotopicMass() . ',';
    echo $peptide->getLength() . ',';

    echo $ppm . ',';
    echo $precursor->getMonoisotopicMassCharge() . ',';
    echo $precursor->getRetentionTime() . ',';
    echo $precursor->getIntensity() . ',';
    echo 1 . ','; // fraction
    echo $precursor->getScan() . ',';
    echo basename($jobRecord['raw_file']) . ',';
    echo $accession . ',';
    echo $ptms . ',';
    echo 0;

    echo PHP_EOL;
}
exit();
