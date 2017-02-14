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
namespace pgb_liv\crowdsource\Core;

class Phase1WorkUnit implements WorkUnitInterface
{

    private $jobId;

    private $precursorId;

    private $fixedModifications = array();

    private $fragmentIons = array();

    private $peptides = array();

    private $fragmentTolerance;

    private $fragmentToleranceUnit;

    public function __construct($jobId, $precursorId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        if (! is_int($precursorId)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be an integer value. Valued passed is of type ' . gettype($precursorId));
        }
        
        $this->jobId = $jobId;
        $this->precursorId = $precursorId;
    }

    public function addFixedModification($monoMass, $residue)
    {
        if (! is_float($monoMass)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be a float value. Valued passed is of type ' . gettype($monoMass));
        }
        
        if (strlen($residue) != 1) {
            throw new \InvalidArgumentException(
                'Argument 2 must be a single char value. Valued passed is of length ' . strlen($residue));
        }
        
        $this->fixedModifications[] = array(
            'mass' => $monoMass,
            'residue' => $residue
        );
    }

    public function addFragmentIon($mz, $intensity)
    {
        if (! is_float($mz)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be a float value. Valued passed is of type ' . gettype($mz));
        }
        
        if (! is_float($intensity)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be a float value. Valued passed is of type ' . gettype($intensity));
        }
        
        $this->fragmentIons[] = array(
            'mz' => $mz,
            'intensity' => $intensity
        );
    }

    public function addPeptide($id, $sequence)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        $this->peptides[$id] = array(
            'sequence' => $sequence,
            'score' => null
        );
    }

    public function addPeptideScore($id, $score)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        if (! is_float($score)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be a float value. Valued passed is of type ' . gettype($score));
        }
        
        if (! isset($this->peptides[$id])) {
            $this->peptides[$id] = array(
                'sequence' => null,
                'score' => null
            );
        }
        
        $this->peptides[$id]['score'] = $score;
    }

    public function setFragmentTolerance($tolerance, $unit)
    {
        if (! is_float($tolerance) && ! is_int($tolerance)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be a float or int value. Valued passed is of type ' . gettype($tolerance));
        }
        
        if ($unit != 'da' && $unit != 'ppm') {
            throw new \InvalidArgumentException(
                'Argument 2 must equal "da" or "ppm". Valued passed is "' . gettype($unit) . '"');
        }
        
        $this->fragmentTolerance = $tolerance;
        $this->fragmentToleranceUnit = $unit;
    }

    public function getJobId()
    {
        return $this->jobId;
    }

    public function getPrecursorId()
    {
        return $this->precursorId;
    }

    public function getPeptides()
    {
        return $this->peptides;
    }

    public function getFixedModifications()
    {
        return $this->fixedModifications;
    }

    public function getFragmentIons()
    {
        return $this->fragmentIons;
    }

    public function getFragmentTolerance()
    {
        return $this->fragmentTolerance;
    }

    public function getFragmentToleranceUnit()
    {
        return $this->fragmentToleranceUnit;
    }

    public function toJson($includeScore = false)
    {
        $data['job'] = $this->jobId;
        $data['precursor'] = $this->precursorId;
        $data['fragments'] = $this->fragmentIons;
        
        // Reformat as we do not want the null score being sent
        $data['peptides'] = array();
        foreach ($this->peptides as $key => $peptide) {
            $data['peptides'][$key] = array();
            $data['peptides'][$key]['sequence'] = $peptide['sequence'];
            if ($includeScore) {
                $data['peptides'][$key]['score'] = $peptide['score'];
            }
        }
        
        $data['fixedMods'] = $this->fixedModifications;
        
        $data['fragTol'] = $this->fragmentTolerance;
        $data['fragTolUnit'] = $this->fragmentToleranceUnit;
        
        return json_encode($data);
    }

    public static function fromJson($jsonStr)
    {
        $jsonObj = json_decode($jsonStr, true);
        
        $workUnit = new Phase1WorkUnit($jsonObj['job'], $jsonObj['precursor']);
        $workUnit->setFragmentTolerance($jsonObj['fragTol'], $jsonObj['fragTolUnit']);
        foreach ($jsonObj['fixedMods'] as $mod) {
            $workUnit->addFixedModification($mod['mass'], $mod['residue']);
        }
        
        foreach ($jsonObj['peptides'] as $key => $peptide) {
            $workUnit->addPeptide($key, $peptide['sequence']);
            
            if (isset($peptide['score'])) {
                $workUnit->addPeptideScore($key, $peptide['score']);
            }
        }
        
        foreach ($jsonObj['fragments'] as $fragment) {
            $workUnit->addFragmentIon($fragment['mz'], $fragment['intensity']);
        }
        
        return $workUnit;
    }
}