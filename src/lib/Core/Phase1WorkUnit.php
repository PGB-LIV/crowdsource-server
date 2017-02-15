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
            'score' => null,
            'ionsMatched' => null
        );
    }

    public function addPeptideScore($id, $score, $ionsMatched = null)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        if (! is_float($score) && ! is_int($score)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be an int or float value. Valued passed is of type ' . gettype($score));
        }
        
        if (! is_int($ionsMatched) && ! is_null($ionsMatched)) {
            throw new \InvalidArgumentException(
                'Argument 3 must be an int value. Valued passed is of type ' . gettype($ionsMatched));
        }
        
        if (! isset($this->peptides[$id])) {
            $this->peptides[$id] = array(
                'sequence' => null,
                'score' => null,
                'ionsMatched' => null
            );
        }
        
        $this->peptides[$id]['score'] = $score;
        
        if (! is_null($ionsMatched)) {
            $this->peptides[$id]['ionsMatched'] = $ionsMatched;
        }
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
        $data = array();
        $data['job'] = $this->jobId;
        $data['precursor'] = $this->precursorId;
        $data['fragments'] = $this->fragmentIons;
        
        // Reformat as we do not want the null score being sent
        $data['peptides'] = array();
        foreach ($this->peptides as $key => $peptide) {
            $peptideData = array();
            $peptideData['id'] = $key;
            $peptideData['sequence'] = $peptide['sequence'];
            if ($includeScore) {
                $peptideData['score'] = $peptide['score'];
            }
            
            $data['peptides'][] = $peptideData;
        }
        
        $data['fixedMods'] = $this->fixedModifications;
        
        $data['fragTol'] = $this->fragmentTolerance;
        $data['fragTolUnit'] = $this->fragmentToleranceUnit;
        
        return json_encode($data);
    }

    public static function fromJson($jsonStr)
    {
        $jsonObj = json_decode($jsonStr, true);
        
        if (is_null($jsonObj)) {
            throw new \InvalidArgumentException('Input must be a valid JSON string value.');
        }
        
        if (! isset($jsonObj['job']) || ! is_int($jsonObj['job'])) {
            throw new \InvalidArgumentException(
                '"job" must be an int value. Valued passed is of type ' . gettype($jsonObj['job']));
        }
        
        if (! isset($jsonObj['precursor']) || ! is_int($jsonObj['precursor'])) {
            throw new \InvalidArgumentException(
                '"precursor" must be an int value. Valued passed is of type ' . gettype($jsonObj['precursor']));
        }
        
        // Initialise object
        $workUnit = new Phase1WorkUnit($jsonObj['job'], $jsonObj['precursor']);
        
        // Parse fragment tolerance
        if (isset($jsonObj['fragTol']) && isset($jsonObj['fragTolUnit'])) {
            $workUnit->setFragmentTolerance($jsonObj['fragTol'], $jsonObj['fragTolUnit']);
        }
        
        // Parse fixed modifications
        if (isset($jsonObj['fixedMods'])) {
            foreach ($jsonObj['fixedMods'] as $mod) {
                $workUnit->addFixedModification($mod['mass'], $mod['residue']);
            }
        }
        
        // Parse peptides
        if (isset($jsonObj['peptides'])) {
            foreach ($jsonObj['peptides'] as $peptide) {
                if (! isset($peptide['id']) || ! is_int($peptide['id'])) {
                    throw new \InvalidArgumentException(
                        'A peptide "ID" must be an int value. Valued passed is of type ' . gettype($peptide['id']));
                }
                
                if (isset($peptide['sequence'])) {
                    $workUnit->addPeptide($peptide['id'], $peptide['sequence']);
                }
                
                if (isset($peptide['score']) && isset($peptide['ionsMatched'])) {
                    $workUnit->addPeptideScore($peptide['id'], $peptide['score'], $peptide['ionsMatched']);
                } else 
                    if (isset($peptide['score'])) {
                        $workUnit->addPeptideScore($peptide['id'], $peptide['score']);
                    }
            }
        }
        
        // Parse fragments
        if (isset($jsonObj['fragments'])) {
            foreach ($jsonObj['fragments'] as $fragment) {
                $workUnit->addFragmentIon($fragment['mz'], $fragment['intensity']);
            }
        }
        return $workUnit;
    }
}