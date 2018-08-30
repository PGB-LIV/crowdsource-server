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

use pgb_liv\crowdsource\BulkQuery;
use pgb_liv\php_ms\Core\Tolerance;

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author Andrew Collins
 */
class WorkUnitPreprocessor
{

    private $adodb;

    private $jobId;

    /**
     * Precursor mass tolerance as ppm value
     *
     * @var Tolerance
     */
    private $massTolerance;

    private $workUnitBulk;

    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }

        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    private function initialise()
    {
        $toleranceRaw = $this->adodb->GetRow(
            'SELECT `precursor_tolerance`, `precursor_tolerance_unit` FROM `job_queue` WHERE `id` = ' . $this->jobId);

        $this->massTolerance = new Tolerance((float) $toleranceRaw['precursor_tolerance'],
            $toleranceRaw['precursor_tolerance_unit']);

        $this->workUnitBulk = new BulkQuery($this->adodb,
            'INSERT INTO `workunit1` (`job`, `precursor`, `peptide`, `modifications`) VALUES ');

        // Ensure tables are clean
        $this->adodb->Execute('DELETE FROM `workunit1` WHERE `job` = ' . $this->jobId);
        $this->adodb->Execute('DELETE FROM `workunit1_locations` WHERE `job` = ' . $this->jobId);
    }

    public function process()
    {
        $this->initialise();
        $this->createWorkUnits();

        $this->workUnitBulk->close();
    }

    private function createWorkUnits()
    {
        $recordSet = $this->adodb->Execute('SELECT `id`, `mass` FROM `raw_ms1` WHERE `job` = ' . $this->jobId);

        // Unmodified
        foreach ($recordSet as $precursor) {
            $peptides = $this->getPeptides($precursor['mass']);

            if (count($peptides) === 0) {
                continue;
            }

            foreach ($peptides as $peptide) {
                $this->workUnitBulk->append(sprintf('(%d, %d, %d, null)', $this->jobId, $precursor['id'], $peptide));
            }
        }

        // Get variable mods
        $modifications = $this->adodb->Execute(
            'SELECT `j`.`mod_id`, `acid`, `mono_mass` FROM `job_variable_mod` `j` LEFT JOIN `unimod_modifications` ON `j`.`mod_id` = `record_id` WHERE `j`.`job` = ' .
            $this->jobId);

        // Indexed
        $modGroup = array();
        foreach ($modifications as $modification) {
            if (! isset($modGroup[$modification['mod_id']])) {
                $modGroup[$modification['mod_id']] = array(
                    'aa' => array()
                );
            }

            $modGroup[$modification['mod_id']]['mass'] = $modification['mono_mass'];
            $modGroup[$modification['mod_id']]['aa'][] = $modification['acid'];
        }

        $set = array();
        foreach ($modGroup as $modId => $modification) {
            for ($i = 0; $i < MAX_MOD_PER_TYPE; $i ++) {
                $set[] = $modId;
            }
        }

        // Generate set of possible mods
        $powerSet = $this->powerSet($set);
        $searchSet = array();

        // Remove empty set, set larger than MAX_MOD_TOTAL and duplicates
        foreach ($powerSet as $set) {
            if (count($set) > MAX_MOD_TOTAL || count($set) == 0) {
                continue;
            }

            sort($set);

            $isFound = false;
            foreach ($searchSet as $search) {
                $isFound = $this->isArrayEqual($set, $search);
                if ($isFound) {
                    break;
                }
            }

            if ($isFound) {
                continue;
            }

            $searchSet[] = $set;
        }

        // Modified
        foreach ($recordSet as $precursor) {
            foreach ($searchSet as $set) {
                $additiveMass = 0;
                $residues = array();
                foreach ($set as $modId) {
                    $additiveMass += $modGroup[$modId]['mass'];
                    $idx = strtolower('`' . implode('_count` + `', $modGroup[$modId]['aa']) . '_count`');

                    if (! isset($residues[$idx])) {
                        $residues[$idx] = 0;
                    }

                    $residues[$idx] ++;
                }

                $peptides = $this->getModifiablePeptides($precursor['mass'] - $additiveMass, $residues);

                if (count($peptides) === 0) {
                    continue;
                }

                foreach ($peptides as $peptide) {
                    $this->workUnitBulk->append(
                        sprintf('(%d, %d, %d, "%s")', $this->jobId, $precursor['id'], $peptide, implode(':', $set)));
                }
            }
        }
    }

    function isArrayEqual($a, $b)
    {
        if (count($a) != count($b)) {
            return false;
        }

        $isEqual = true;
        for ($i = 0; $i < count($a); $i ++) {
            if ($a[$i] != $b[$i]) {
                $isEqual = false;
                break;
            }
        }

        return $isEqual;
    }

    /**
     * Get the peptides that have a mass matching the spectra mass
     *
     * @param float $spectraMass
     *            Mass of the spectra to match against
     * @return array Peptide IDs within tolerance
     */
    private function getPeptides($spectraMass)
    {
        $tolerance = $this->massTolerance->getDaltonDelta($spectraMass);
        $pepMassLow = $spectraMass - $tolerance;
        $pepMassHigh = $spectraMass + $tolerance;

        return $this->adodb->GetCol(
            'SELECT `peptide` FROM `fasta_peptide_fixed` WHERE `job` = ' . $this->jobId . ' && `fixed_mass` BETWEEN ' .
            $pepMassLow . ' AND ' . $pepMassHigh);
    }

    /**
     * Get the peptides that have a mass matching the spectra mass and apply where
     *
     * @param float $spectraMass
     *            Mass of the spectra to match against
     * @return array Peptide IDs within tolerance
     */
    private function getModifiablePeptides($spectraMass, $where)
    {
        $fastaId = 1;
        $tolerance = $this->massTolerance->getDaltonDelta($spectraMass);
        $pepMassLow = $spectraMass - $tolerance;
        $pepMassHigh = $spectraMass + $tolerance;

        $query = 'SELECT `f`.`peptide` FROM `fasta_peptide_fixed` `f`
            LEFT JOIN `fasta_peptides` `p` ON `f`.`peptide` = `p`.`id` && `fasta` = ' . $fastaId . '
            WHERE `job` = ' . $this->jobId . ' && `fixed_mass` BETWEEN ' .
            $pepMassLow . ' AND ' . $pepMassHigh;

        foreach ($where as $key => $value) {
            $query .= ' && ' . $key . ' >= ' . $value;
        }

        return $this->adodb->GetCol($query);
    }

    function powerSet($array)
    {
        // add the empty set
        $results = array(
            array()
        );

        foreach ($array as $element) {
            foreach ($results as $combination) {
                $results[] = array_merge(array(
                    $element
                ), $combination);
            }
        }

        return $results;
    }
}
