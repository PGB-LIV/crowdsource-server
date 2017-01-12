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
use pgb_liv\php_ms\Core\Spectra\SpectraEntry;

/**
 *
 * @author Andrew Collins
 */
class RawPreprocessor
{

    private $adodb;

    private $rawParser;

    private $maxPeaks = - 1;

    private $ms1Bulk;

    private $ms2Bulk;

    private $jobId;

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn            
     * @param MgfReader $rawParser            
     * @param int $jobId            
     */
    public function __construct($conn, MgfReader $rawParser, $jobId)
    {
        $this->adodb = $conn;
        $this->rawParser = $rawParser;
        $this->jobId = $jobId;
    }

    /**
     * Sets the max number of peaks the MS2 level data is allowed to contain.
     * The highest intensity peaks are used for selection.
     *
     * @param int $maxPeaks            
     */
    public function setMs2PeakCount($maxPeaks)
    {
        $this->maxPeaks = $maxPeaks;
    }

    private function processMs1($id, SpectraEntry $ms1)
    {
        $this->ms1Bulk->append(
            sprintf('(%d, %d, %s, %f, %d, %d, %f)', $id, $this->jobId, $this->adodb->quote($ms1->getTitle()), $ms1->getMassCharge(), $ms1->getCharge(), $ms1->getScans(), 
                $ms1->getRetentionTime()));
    }

    private function filterMs2(array $ms2)
    {
        if (count($ms2) < $this->maxPeaks) {
            return $ms2;
        }
        
        $filteredMs2 = array();
        $intensities = array();
        
        foreach ($ms2 as $entry) {
            $intensities[] = $entry->getIntensity();
        }
        
        rsort($intensities);
        
        $threshold = $intensities[$this->maxPeaks - 1];
        
        foreach ($ms2 as $entry) {
            if ($entry->getIntensity() >= $threshold) {
                $filteredMs2[] = $entry;
            }
        }
        
        return $filteredMs2;
    }

    private function processMs2($ms1, SpectraEntry $ms2)
    {
        $ms2 = $this->filterMs2($ms2->getIons());
        $ms2Id = 1;
        foreach ($ms2 as $ion) {
            $this->ms2Bulk->append(sprintf('(%d, %d, %d, %f, %f)', $ms2Id, $ms1, $this->jobId, $ion->getMassCharge(), $ion->getIntensity()));
            
            $ms2Id ++;
        }
    }

    private function processRawFile()
    {
        $ms1Id = 1;
        
        foreach ($this->rawParser as $rawEntry) {
            if ($rawEntry->getMassCharge() == null) {
                continue;
            }
            
            $this->processMs1($ms1Id, $rawEntry);
            $this->processMs2($ms1Id, $rawEntry);
            $ms1Id ++;
        }
    }

    public function process()
    {
        $this->ms1Bulk = new BulkQuery($this->adodb, 'INSERT IGNORE INTO `raw_ms1` (`id`, `job`, `title`, `pepmass`, `charge`, `scans`, `rtinseconds`) VALUES');
        $this->ms2Bulk = new BulkQuery($this->adodb, 'INSERT IGNORE INTO `raw_ms2` (`id`, `ms1`, `job`, `mz`, `intensity`) VALUES');
                
        $this->processRawFile();
        
        $this->ms1Bulk->close();
        $this->ms2Bulk->close();        
    }
}
