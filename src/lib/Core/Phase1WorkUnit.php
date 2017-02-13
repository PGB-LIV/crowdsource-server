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

class Phase1WorkUnit
{

    private $jobId;

    private $precursorId;

    private $fixedModifications = array();

    private $fragmentIons = array();

    private $peptides = array();

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
        
        if (! is_float($residue)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be a float value. Valued passed is of type ' . gettype($residue));
        }
        
        $this->fixedModifications[] = array(
            'mass' => $monoMass,
            'aa' => $residue
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
                'Argument 1 must be a float value. Valued passed is of type ' . gettype($id));
        }
        
        $this->peptides[] = array(
            'id' => $id,
            'sequence' => $sequence,
            'score' => null
        );
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
}