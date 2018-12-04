<?php
use pgb_liv\php_ms\Reader\MzIdentMlReader1r2;
use pgb_liv\php_ms\Search\Parameters\MsgfPlusSearchParameters;
use pgb_liv\php_ms\Search\MsgfPlusSearch;
use pgb_liv\php_ms\Statistic\FalseDiscoveryRate;
use pgb_liv\crowdsource\Core\Modification;

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

error_reporting(E_ALL);
ini_set('display_errors', true);

chdir(__DIR__);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/autoload.php';

$jobId = 2;
$mzidPath = DATA_PATH . '/' . $jobId . '/results/results.mzid';

echo 'Reading ' . $mzidPath . PHP_EOL;
$reader = new MzIdentMlReader1r2($mzidPath);

$dataCollection = $reader->getDataCollection();
$database = DATA_PATH . '/databases/curated/' . $dataCollection['inputs']['SearchDatabase']['SDB_0']['location'];
$tmp = scandir(DATA_PATH . '/' . $jobId);
foreach ($tmp as $input) {
    if (substr($input, - 3) == 'mgf') {
        $spectra = DATA_PATH . '/' . $jobId . '/' . $input;
    }
}

$benchmarkPath = DATA_PATH . '/' . $jobId . '/benchmark';
if (! file_exists($benchmarkPath)) {
    mkdir($benchmarkPath);
}

$tmp = $benchmarkPath . '/' . basename($database);
copy($database, $tmp);
$database = $tmp;

echo 'Selecting ' . $database . PHP_EOL;
echo 'Selecting ' . $spectra . PHP_EOL;

// MS-GF+

$protocolCollection = $reader->getAnalysisProtocolCollection();

foreach ($protocolCollection['spectrum'] as $protocol) {

    $fragmentTolerance = current($protocol['fragmentTolerance']);
    $precursorTolerance = current($protocol['parentTolerance']);
    $enzyme = current($protocol['enzymes'])['EnzymeName']['name'];
    $cleavage = current($protocol['enzymes'])['missedCleavages'];
    $modifications = $protocol['modifications'];
}

// Merge modifications
for ($modId = 0; $modId < count($modifications); $modId ++) {
    if (! isset($modifications[$modId])) {
        continue;
    }

    $modification = $modifications[$modId];
    foreach ($modifications as $duplicateId => $duplicate) {
        if ($modId == $duplicateId) {
            continue;
        }

        // TODO: phpMs needs to set accession
        if ($modification->getName() != $duplicate->getName()) {
            continue;
        }

        if ($modification->getPosition() != $duplicate->getPosition()) {
            continue;
        }

        $residues = array_merge($modification->getResidues(), $duplicate->getResidues());

        $modification->setResidues($residues);
        unset($modifications[$duplicateId]);
    }
}

$msgfConf = new MsgfPlusSearchParameters();
$msgfConf->setDatabases($database);
$msgfConf->setSpectraPath($spectra);
$msgfConf->setOutputFile($benchmarkPath . '/msgf.mzid');

$msgfConf->setFragmentTolerance($fragmentTolerance);
$msgfConf->setMissedCleavageCount($cleavage);
$msgfConf->setPrecursorTolerance($precursorTolerance);
$msgfConf->setMs2DetectorId(3);
$msgfConf->setMinPrecursorCharge(1);
$msgfConf->setMaxPrecursorCharge(255);
$msgfConf->setMaxPeptideLength(60);
$msgfConf->setDecoyEnabled(true);

$modFile = $msgfConf->createModificationFile($modifications, 4);
copy($modFile, $benchmarkPath . '/msgf_mods.txt');

foreach ($modifications as $modification) {
    $msgfConf->addModification($modification);
}

$msGf = new MsgfPlusSearch('/mnt/nas/_CLUSTER_SOFTWARE/ms-gf+/current/MSGFPlus.jar');
// $msGf->search($msgfConf);

$settings = $benchmarkPath . '/msamanda.xml';

$settingsData = '<?xml version="1.0" encoding="UTF-8"?>
<settings>
<search_settings>
<enzyme specificity="FULL">' . $enzyme . '</enzyme>
<missed_cleavages>' . $cleavage . '</missed_cleavages>
<modifications>
';

foreach ($modifications as $modification) {
    if ($modification->getName() == 'Methyl') {
        continue;
    }
    $settingsData .= '<modification';

    if ($modification->isFixed()) {
        $settingsData .= ' fix="true"';
    }

    if ($modification->getPosition() == Modification::POSITION_CTERM) {
        $settingsData .= ' cterm="true"';
    }

    if ($modification->getPosition() == Modification::POSITION_NTERM) {
        $settingsData .= ' nterm="true"';
    }

    $settingsData .= '>' . $modification->getName();

    if (count($modification->getResidues()) > 0) {
        $settingsData .= '(' . implode(',', $modification->getResidues()) . ')';
    }

    $settingsData .= '</modification>' . PHP_EOL;
}

$settingsData .= '</modifications>
<instrument>b, y</instrument>
<ms1_tol unit="' .
    $precursorTolerance->getUnit() . '">' . $precursorTolerance->getTolerance() . '</ms1_tol>
<ms2_tol unit="' .
    $fragmentTolerance->getUnit() . '">' . $fragmentTolerance->getTolerance() . '</ms2_tol>
<max_rank>1</max_rank>
<generate_decoy>true</generate_decoy>
<PerformDeisotoping>true</PerformDeisotoping>
<MaxNoModifs>3</MaxNoModifs>
<MaxNoDynModifs>4</MaxNoDynModifs>
<MaxNumberModSites>6</MaxNumberModSites>
<MaxNumberNeutralLoss>1</MaxNumberNeutralLoss>
<MaxNumberNeutralLossModifications>2</MaxNumberNeutralLossModifications>
<MinimumPepLength>6</MinimumPepLength>
</search_settings>
<basic_settings>
<instruments_file>/mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/Instruments.xml</instruments_file>
<unimod_file>/mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/unimod.xml</unimod_file>
<enzyme_file>/mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/enzymes.xml</enzyme_file>
<unimod_obo_file>/mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/unimod.obo</unimod_obo_file>
<psims_obo_file>/mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/psi-ms.obo</psims_obo_file>
<monoisotopic>true</monoisotopic>
<considered_charges>1+,2+,3+,4+,5+,6+,7+</considered_charges>
<LoadedProteinsAtOnce>100000</LoadedProteinsAtOnce>
<LoadedSpectraAtOnce>4000</LoadedSpectraAtOnce>
<data_folder>DEFAULT</data_folder>
</basic_settings>
</settings>';

file_put_contents($settings, $settingsData);

// MSAmanda
$cmd = 'mono /mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/MSAmanda.exe -s "' . $spectra . '" -d "' . $database .
    '" -e "' . $settings . '" -f 2 -o "' . $benchmarkPath . '/msamanda.mzid"';
echo $cmd . PHP_EOL;

// echo `$cmd`;

/*
 * FDR
 */
$benchmark = array();

$mzidPath = DATA_PATH . '/' . $jobId . '/results/results.mzid';
$reader = new MzIdentMlReader1r2($mzidPath);
$data = $reader->getAnalysisData();
$identifications = array();
foreach ($data as $spectra) {
    foreach ($spectra->getIdentifications() as $identification) {
        if ($identification->getRank() == 1) {
            $identifications[] = $identification;
        }
    }
}

$fdr = new FalseDiscoveryRate($identifications, 'MS:1002352');

$benchmark['CrowdSource'] = array(
    '0.01' => $fdr->getMatches(0.01),
    '0.05' => $fdr->getMatches(0.05),
    '1' => count($identifications)
);

$mzidPath = DATA_PATH . '/' . $jobId . '/benchmark/msgf.mzid';
$reader = new MzIdentMlReader1r2($mzidPath);
$data = $reader->getAnalysisData();
$identifications = array();
foreach ($data as $spectra) {
    foreach ($spectra->getIdentifications() as $identification) {
        if ($identification->getRank() == 1) {
            $identifications[] = $identification;
        }
    }
}
$fdr = new FalseDiscoveryRate($identifications, 'MS:1002053', SORT_ASC);

$benchmark['MS-GF+'] = array(
    '0.01' => $fdr->getMatches(0.01),
    '0.05' => $fdr->getMatches(0.05),
    '1' => count($identifications)
);

$mzidPath = DATA_PATH . '/' . $jobId . '/benchmark/msamanda.mzid';
$reader = new MzIdentMlReader1r2($mzidPath);
$data = $reader->getAnalysisData();
$identifications = array();
foreach ($data as $spectra) {
    foreach ($spectra->getIdentifications() as $identification) {
        if ($identification->getRank() == 1) {
            $identifications[] = $identification;
        }
    }
}

$fdr = new FalseDiscoveryRate($identifications, 'MS:1002319');

$benchmark['MSAmanda'] = array(
    '0.01' => $fdr->getMatches(0.01),
    '0.05' => $fdr->getMatches(0.05),
    '1' => count($identifications)
);

foreach ($benchmark as $searchEngine => $results) {
    echo $searchEngine . "\t" . $results['0.01'] . "\t" . $results['0.05'] . "\t" . $results['1'] . PHP_EOL;
}
