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
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\php_ms\Utility\Filter\FilterCharge;
use pgb_liv\php_ms\Utility\Filter\FilterMass;
use pgb_liv\php_ms\Core\Spectra\PrecursorIon;
use pgb_liv\crowdsource\Core\FragmentIon;
use pgb_liv\php_ms\Utility\Sort\IonSort;

/**
 *
 * @author Andrew Collins
 */
class RawPreprocessor
{

    private $adodb;

    private $rawParser;

    private $maxPeaks = - 1;

    private $windowSize = 100;

    private $ms1Bulk;

    private $ms2Bulk;

    private $jobId;

    private $filterCharge;

    private $filterMass;

    public function __construct(\ADOConnection $conn, MgfReader $rawParser, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }

        $this->adodb = $conn;
        $this->rawParser = $rawParser;
        $this->jobId = $jobId;
    }

    /**
     * Sets the max number of peaks the MS2 level data is allowed to contain.
     * The highest intensity peaks are used for selection.
     *
     * @param int $maxPeaks
     *            Maximum number of peaks to analyse. Ordered by intensity
     */
    public function setMs2PeakCount($maxPeaks, $windowSize)
    {
        if (! is_int($maxPeaks)) {
            throw new \InvalidArgumentException(
                'Max peaks must be an integer value. Valued passed is of type ' . gettype($maxPeaks));
        }

        if (! is_int($windowSize)) {
            throw new \InvalidArgumentException(
                'Peak window size must be an integer value. Valued passed is of type ' . gettype($windowSize));
        }

        $this->maxPeaks = $maxPeaks;
        $this->windowSize = $windowSize;
    }

    private function processMs1($id, PrecursorIon $ms1)
    {
        $this->ms1Bulk->append(
            sprintf('(%d, %d, %s, %f, %f, %d, %d, %f)', $id, $this->jobId, $this->adodb->quote($ms1->getTitle()),
                $ms1->getMonoisotopicMassCharge(), $ms1->getMonoisotopicMass(), $ms1->getCharge(), $ms1->getScan(),
                $ms1->getRetentionTime()));
    }

    /**
     *
     * @param FragmentIon $ms2
     * @return FragmentIon[]
     */
    private function filterMs2(array $ms2)
    {
        if (count($ms2) < $this->maxPeaks) {
            return $ms2;
        }

        $sort = new IonSort(IonSort::SORT_INTENSITY, SORT_DESC);
        $sort->sort($ms2);

        $bins = array();
        // Adds each ion to relevant bin. Bins sorted by intensity
        foreach ($ms2 as $ion) {
            $binId = floor($ion->getMonoisotopicMassCharge() / 100) * 100;

            if (! isset($bins[$binId])) {
                $bins[$binId] = array();
            }

            $bins[$binId][] = $ion;
        }

        $filteredMs2 = array();
        $depth = 0;
        $dataRemaining = false;
        do {
            foreach ($bins as $bin) {
                if (! isset($bin[$depth])) {
                    continue;
                }

                $dataRemaining = true;
                $filteredMs2[] = $bin[$depth];
            }

            $depth ++;
        } while (count($filteredMs2) < $this->maxPeaks && $dataRemaining);

        return $filteredMs2;
    }

    private function processMs2($ms1, PrecursorIon $ms2)
    {
        if ($this->maxPeaks != - 1) {
            $ms2Ions = $this->filterMs2($ms2->getFragmentIons());
        } else {
            $ms2Ions = $ms2->getFragmentIons();
        }

        $ms2Id = 1;

        foreach ($ms2Ions as $ion) {
            $this->ms2Bulk->append(
                sprintf('(%d, %d, %d, %f, %f)', $ms2Id, $ms1, $this->jobId, $ion->getMonoisotopicMassCharge(),
                    $ion->getIntensity()));

            $ms2Id ++;
        }
    }

    private function processRawFile()
    {
        $ms1Id = 1;

        foreach ($this->rawParser as $spectra) {
            if (! $this->filterCharge->isValidSpectra($spectra)) {
                continue;
            }

            $this->processMs1($ms1Id, $spectra);
            $this->processMs2($ms1Id, $spectra);
            $ms1Id ++;
        }
    }

    public function process()
    {
        $this->initialise();

        $this->processRawFile();

        $this->ms1Bulk->close();
        $this->ms2Bulk->close();
    }

    private function initialise()
    {
        // TODO: Tolerance needs to factor in it could be Da
        $job = $this->adodb->GetRow(
            'SELECT `charge_min`, `charge_max`, `precursor_tolerance` FROM `job_queue` WHERE `id` = ' . $this->jobId);
        $tolerance = $job['precursor_tolerance'] / 1000000;

        $massLimt = $this->adodb->GetRow(
            'SELECT MIN(`mono_mass`) AS `min`, MAX(`mono_mass`) AS `max` FROM `unimod_modifications`;');

        $mass = $this->adodb->GetRow(
            'SELECT MIN(`fixed_mass`) AS `min`, MAX(`fixed_mass`) AS `max` FROM `fasta_peptide_fixed` WHERE `job` = ' .
            $this->jobId);

        $mass['min'] -= $massLimt['min'];
        $mass['max'] -= $massLimt['max'];

        $mass['min'] -= $mass['min'] * $tolerance;
        $mass['max'] += $mass['max'] * $tolerance;

        $this->filterCharge = new FilterCharge((int) $job['charge_min'], (int) $job['charge_max']);
        $this->filterMass = new FilterMass((float) $mass['min'], (float) $mass['max']);

        $this->ms1Bulk = new BulkQuery($this->adodb,
            'INSERT IGNORE INTO `raw_ms1` (`id`, `job`, `title`, `mass_charge`, `mass`, `charge`, `scans`, `rtinseconds`) VALUES');
        $this->ms2Bulk = new BulkQuery($this->adodb,
            'INSERT IGNORE INTO `raw_ms2` (`id`, `ms1`, `job`, `mz`, `intensity`) VALUES');
        
        // Ensure tables are clean
        $this->adodb->Execute('DELETE FROM `raw_ms1` WHERE `job` = '. $this->jobId);
        $this->adodb->Execute('DELETE FROM `raw_ms2` WHERE `job` = '. $this->jobId);
    }
}
