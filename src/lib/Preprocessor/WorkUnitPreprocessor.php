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

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author acollins
 *        
 */
class WorkUnitPreprocessor
{

    private $adodb;

    private $jobId;

    private $massTolerance;

    private $workUnitBulk;

    private $workUnitPeptideBulk;

    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException('Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    private function initialise()
    {
        $this->massTolerance = $this->adodb->GetOne('SELECT `mass_tolerance` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        // As ppm
        $this->massTolerance /= 1000000;
        
        $this->workUnitBulk = new BulkQuery($this->adodb, 'INSERT INTO `workunit` (`id`, `job`, `ms1`) VALUES ');
        $this->workUnitPeptideBulk = new BulkQuery($this->adodb, 'INSERT INTO `workunit_peptides` (`workunit`, `job`, `peptide`) VALUES ');
    }

    /**
     * Calculates the ppm tolerance of the specified peptide mass
     *
     * @param float $peptideMass
     *            Peptide mass to calculate tolerance for
     * @return float Tolerance value
     */
    private function calculateTolerance($peptideMass)
    {
        return $peptideMass * $this->massTolerance;
    }

    private function getPeptides($peptideMass)
    {
        $tolerance = $this->calculateTolerance($peptideMass);
        $pepMassLow = $peptideMass - $tolerance;
        $pepMassHigh = $peptideMass + $tolerance;
        
        return $this->adodb->GetCol(
            'SELECT `id` FROM `fasta_peptides` WHERE `job` = ' . $this->jobId . ' && `mass` BETWEEN ' . $pepMassLow . ' AND ' . $pepMassHigh);
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
        $recordSet = $this->adodb->Execute('SELECT `id`, `pepmass`, `charge` FROM `raw_ms1` WHERE `job` = ' . $this->jobId);
        
        $workUnitId = 1;
        foreach ($recordSet as $record) {
            $peptides = $this->getPeptides($record['pepmass']);
            
            if (count($peptides) === 0) {
                continue;
            }
            
            $this->workUnitBulk->append(sprintf('(%d, %d, %d)', $workUnitId, $this->jobId, $record['id']));
            
            foreach ($peptides as $peptide) {
                $this->workUnitPeptideBulk->append(sprintf('(%d, %d, %d)', $workUnitId, $this->jobId, $peptide));
            }
            
            $workUnitId ++;
        }
    }
}
