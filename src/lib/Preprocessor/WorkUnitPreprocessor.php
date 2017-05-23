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

    private $workUnitPeptideBulk;

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
        
        $this->workUnitBulk = new BulkQuery($this->adodb, 'INSERT INTO `workunit1` (`job`, `precursor`) VALUES ');
        $this->workUnitPeptideBulk = new BulkQuery($this->adodb, 
            'INSERT INTO `workunit1_peptides` (`job`, `precursor`, `peptide`) VALUES ');
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
            'SELECT `id` FROM `fasta_peptides` WHERE `job` = ' . $this->jobId . ' && `mass_modified` BETWEEN ' .
                 $pepMassLow . ' AND ' . $pepMassHigh);
    }

    public function process()
    {
        $this->initialise();
        $this->createWorkUnits();
        
        $this->workUnitBulk->close();
        $this->workUnitPeptideBulk->close();
    }

    private function createWorkUnits()
    {
        $recordSet = $this->adodb->Execute('SELECT `id`, `mass`, `charge` FROM `raw_ms1` WHERE `job` = ' . $this->jobId);
        
        foreach ($recordSet as $precursor) {
            $peptides = $this->getPeptides($precursor['mass']);
            
            if (count($peptides) === 0) {
                continue;
            }
            
            $this->workUnitBulk->append(sprintf('(%d, %d)', $this->jobId, $precursor['id']));
            
            foreach ($peptides as $peptide) {
                $this->workUnitPeptideBulk->append(sprintf('(%d, %d, %d)', $this->jobId, $precursor['id'], $peptide));
            }
        }
    }
}
