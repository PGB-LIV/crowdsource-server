<?php
use pgb_liv\php_ms\Reader\MzIdentMlReader1r2;
use pgb_liv\php_ms\Reader\HupoPsi\PsiVerb;
use pgb_liv\php_ms\Search\Parameters\MsgfPlusSearchParameters;
use pgb_liv\php_ms\Search\MsgfPlusSearch;
use pgb_liv\php_ms\Statistic\FalseDiscoveryRate;
use pgb_liv\crowdsource\Core\Modification;
use pgb_liv\php_ms\Core\Peptide;

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
ini_set('memory_limit', '8G');

chdir(__DIR__);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/autoload.php';

function Ident2String(Peptide $peptide)
{
    $mods = array();
    foreach ($peptide->getModifications() as $modification) {
        $mods[$modification->getLocation()] = $modification->getMonoisotopicMass();
    }
    
    ksort($mods);
    
    $pepStr = $peptide->getSequence();
    
    $seqRet = '';
    
    for ($i = 0; $i < strlen($pepStr); $i ++) {
        $seqRet .= $pepStr[$i];
        
        if (isset($mods[$i + 1])) {
            $seqRet .= '[' . $mods[$i + 1] . ']';
        }
    }
    
    return $seqRet;
}

function getInputs(MzIdentMlReader1r2 $reader, array &$benchmark)
{
    $inputs = $reader->getInputs();

    $benchmark['database'] = '';
    foreach ($inputs['SearchDatabase'] as $database) {
        $benchmark['database'] .= basename($database['location']) . '<br />';
    }

    $benchmark['spectra'] = '';
    foreach ($inputs['SpectraData'] as $spectra) {
        $benchmark['spectra'] .= basename($spectra['location']) . '<br />';
    }
}

function getProtocol(MzIdentMlReader1r2 $reader, array &$benchmark)
{
    $protocolCollection = $reader->getAnalysisProtocolCollection();

    foreach ($protocolCollection['spectrum'] as $protocol) {
        $benchmark['name'] = $protocol['software']['name'];
        $benchmark['version'] = $protocol['software']['version'];

        if (isset($protocol['software']['version'])) {
            $benchmark['version'] = $protocol['software']['version'];
        }

        if (isset($protocol['fragmentTolerance']) || isset($protocol['parentTolerance'])) {
            if (isset($protocol['fragmentTolerance'])) {
                foreach ($protocol['fragmentTolerance'] as $tolerance) {
                    $benchmark['fragTol'] = $tolerance->getTolerance() . ' ' . $tolerance->getUnit();
                }
            }

            if (isset($protocol['parentTolerance'])) {
                foreach ($protocol['parentTolerance'] as $tolerance) {
                    $benchmark['precTol'] = $tolerance->getTolerance() . ' ' . $tolerance->getUnit();
                }
            }
        }

        if (isset($protocol['enzymes'])) {
            foreach ($protocol['enzymes'] as $enzyme) {
                if (isset($enzyme['EnzymeName']['name'])) {
                    $name = $enzyme['EnzymeName']['name'];
                } else {
                    $name = $enzyme['id'];
                }

                $benchmark['enzyme'] = $name;
                $benchmark['missedCleavages'] = $enzyme['missedCleavages'];
            }
        }

        if (isset($protocol['modifications'])) {
            $benchmark['mods'] = '';

            foreach ($protocol['modifications'] as $modification) {
                $benchmark['mods'] .= '[' . ($modification->isFixed() ? 'F' : 'V') . '] ' . $modification->getName() .
                    ' (' . implode(',', $modification->getResidues());

                if ($modification->getPosition() != Modification::POSITION_ANY) {
                    $benchmark['mods'] .= '@' . $modification->getPosition();
                }

                $benchmark['mods'] .= ') ' . $modification->getMonoisotopicMass() . ' ' . '<br />';
            }
        }
    }

    if (isset($protocolCollection['protein'])) {
        $protocol = $protocolCollection['protein'];

        $benchmark['threshold'] = '';
        foreach ($protocol['threshold'] as $threshold) {
            $benchmark['threshold'] .= $threshold[PsiVerb::CV_ACCESSION] . ': ' . $threshold['name'];
        }
    }
}

function getBenchmark($mzidPath, $scoreKey, array &$spectra2Ident, $sort = SORT_DESC)
{
    $benchmark = array();

    $reader = new MzIdentMlReader1r2($mzidPath);
    $reader->setRankFilter(1);

    getInputs($reader, $benchmark);
    getProtocol($reader, $benchmark);

    $data = $reader->getAnalysisData();
    
    $spectra2Ident = array();
    $identifications = array();
    foreach ($data as $spectra) {
        foreach ($spectra->getIdentifications() as $identification) {
            if (isset($identifications[$spectra->getIdentifier()])) {
                $oldId = $identifications[$spectra->getIdentifier()];

                if ($oldId->getScore($scoreKey) == $identification->getScore($scoreKey)) {
                    $isDecoy = false;
                    if ($oldId->getSequence()->isDecoy()) {
                        $isDecoy = true;
                    }

                    foreach ($oldId->getSequence()->getProteins() as $proteinEntry) {
                        $protein = $proteinEntry->getProtein();
                        if ($protein->isDecoy()) {
                            $isDecoy = true;
                            break;
                        }
                    }

                    // Old ID not a decoy. Keep it
                    if (! $isDecoy) {
                        continue;
                    }
                }

                if ($sort == SORT_DESC && $oldId->getScore($scoreKey) > $identification->getScore($scoreKey)) {
                    continue;
                }

                if ($sort == SORT_ASC && $oldId->getScore($scoreKey) < $identification->getScore($scoreKey)) {
                    continue;
                }
            }

            $identifications[$spectra->getIdentifier()] = $identification;
            $spectra2Ident[$spectra->getTitle()] = Ident2String($identification->getSequence());
        }
    }

    $data = null;

    $fdr = new FalseDiscoveryRate($identifications, $scoreKey, $sort);

    $benchmark['0.01'] = $fdr->getMatches(0.01);
    $benchmark['0.05'] = $fdr->getMatches(0.05);
    $benchmark['1'] = count($identifications);

    return $benchmark;
}

$jobId = 1;

if (isset($argv[1])) {
    $jobId = $argv[1];
}

$mzidPath = DATA_PATH . '/' . $jobId . '/results/results.mzid';

echo 'Reading ' . $mzidPath . PHP_EOL;
$reader = new MzIdentMlReader1r2($mzidPath);

$inputs = $reader->getInputs();
$database = DATA_PATH . '/databases/curated/' . $inputs['SearchDatabase']['SDB_0']['location'];

unset($inputs);
$tmp = scandir(DATA_PATH . '/' . $jobId);
foreach ($tmp as $input) {
    if (substr($input, - 3) == 'mgf') {
        $spectra = DATA_PATH . '/' . $jobId . '/' . $input;
    }
}
echo 'Selecting ' . $spectra . PHP_EOL;

$benchmarkPath = DATA_PATH . '/' . $jobId . '/benchmark';
if (! file_exists($benchmarkPath)) {
    mkdir($benchmarkPath);
}

echo 'Copying ' . $database . PHP_EOL;
$tmp = $benchmarkPath . '/' . basename($database);
copy($database, $tmp);
$database = $tmp;

echo 'Selecting ' . $database . PHP_EOL;

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

unset($protocolCollection);
unset($reader);

// MS-GF+
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

$msGf = new MsgfPlusSearch('/mnt/nas/_CLUSTER_SOFTWARE/ms-gf+/2018.10.15/MSGFPlus.jar');

echo 'Running MS-GF+... ';
if (! file_exists($benchmarkPath . '/msgf.mzid')) {
    $msGf->search($msgfConf);
}

echo 'Done ' . PHP_EOL;

$settings = $benchmarkPath . '/msamanda.xml';

$settingsData = '<?xml version="1.0" encoding="UTF-8"?>
<settings>
<search_settings>
<enzyme specificity="FULL">' . $enzyme . '</enzyme>
<missed_cleavages>' . $cleavage . '</missed_cleavages>
<modifications>
';

foreach ($modifications as $modification) {
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
<instruments_file>Instruments.xml</instruments_file>
<unimod_file>unimod.xml</unimod_file>
<enzyme_file>enzymes.xml</enzyme_file>
<unimod_obo_file>unimod.obo</unimod_obo_file>
<psims_obo_file>psi-ms.obo</psims_obo_file>
<monoisotopic>true</monoisotopic>
<considered_charges>1+,2+,3+,4+,5+,6+,7+</considered_charges>
<LoadedProteinsAtOnce>100000</LoadedProteinsAtOnce>
<LoadedSpectraAtOnce>4000</LoadedSpectraAtOnce>
<data_folder>DEFAULT</data_folder>
</basic_settings>
</settings>';

file_put_contents($settings, $settingsData);

// MSAmanda
$cmd = 'ssh server1 "cd /mnt/software/MSAmanda/2.0.0.11219/;mono MSAmanda.exe -s \"' . $spectra . '\" -d \"' . $database .
    '\" -e \"' . $settings . '\" -f 2 -o \"' . $benchmarkPath . '/msamanda.mzid\""';
echo $cmd . PHP_EOL;

echo 'Running MSAmanda+... ';
if (! file_exists($benchmarkPath . '/msamanda.mzid')) {
    $cwd = getcwd();
    chdir('/mnt/nas/_CLUSTER_SOFTWARE/MSAmanda/2.0.0.11219/');
    echo `$cmd`;
    chdir($cwd);
}

echo 'Done ' . PHP_EOL;

echo 'Calculating FDR' . PHP_EOL;
/*
 * FDR
 */
$benchmark = array();
$spectra2Id = array();
echo 'Calculating Dracula' . PHP_EOL;
$benchmark[] = getBenchmark(DATA_PATH . '/' . $jobId . '/results/results.mzid', 'MS:1002352', $spectra2Id);
file_put_contents($benchmarkPath . '/dracula.json', json_encode($spectra2Id, JSON_PRETTY_PRINT));

foreach ($benchmark as $searchEngine => $results) {
    echo str_pad($searchEngine, 15, ' ', STR_PAD_RIGHT) . $results['0.01'] . "\t" . $results['0.05'] . "\t" . $results['1'] . PHP_EOL;
}

echo 'Calculating MS-GF+' . PHP_EOL;
$benchmark[] = getBenchmark($benchmarkPath . '/msgf.mzid', 'MS:1002053', $spectra2Id, SORT_ASC);
file_put_contents($benchmarkPath . '/msgf.json', json_encode($spectra2Id, JSON_PRETTY_PRINT));

echo 'Calculating MSAmanda' . PHP_EOL;
$benchmark[] = getBenchmark($benchmarkPath . '/msamanda.mzid', 'MS:1002319', $spectra2Id);
file_put_contents($benchmarkPath . '/msamanda.json', json_encode($spectra2Id, JSON_PRETTY_PRINT));

if (file_exists($benchmarkPath . '/peptides_1_1_0.mzid')) {
    echo 'Calculating Peaks' . PHP_EOL;
    $benchmark[] = getBenchmark($benchmarkPath . '/peptides_1_1_0.mzid', 'MS:1001950', $spectra2Id);
    file_put_contents($benchmarkPath . '/peaks.json', json_encode($spectra2Id, JSON_PRETTY_PRINT));
}

file_put_contents($benchmarkPath . '/benchmarks.json', json_encode($benchmark, JSON_PRETTY_PRINT));

echo str_pad('SearchEngine', 15, ' ', STR_PAD_RIGHT) . "1%\t5%\tTotal" . PHP_EOL;

foreach ($benchmark as $searchEngine => $results) {
    echo str_pad($searchEngine, 15, ' ', STR_PAD_RIGHT) . $results['0.01'] . "\t" . $results['0.05'] . "\t" .
        $results['1'] . PHP_EOL;
}
