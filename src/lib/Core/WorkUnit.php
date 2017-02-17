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

class WorkUnit
{

    const JSON_PEPTIDES = 'peptides';

    const JSON_PEPTIDE_ID = 'id';

    const JSON_PEPTIDE_SEQUENCE = 'sequence';

    const JSON_PEPTIDE_SCORE = 'score';

    const JSON_PEPTIDE_IONS = 'ionsMatched';

    const JSON_JOB = 'job';

    const JSON_PRECURSOR = 'precursor';

    const JSON_FRAGMENTS = 'fragments';

    const JSON_FIXED_MODIFICATIONS = 'fixedMods';

    const JSON_PEPTIDE_MODIFICATIONS = 'mods';

    const JSON_MODIFICATION_ID = 'id';

    const JSON_MODIFICATION_MASS = 'mass';

    const JSON_MODIFICATION_RESIDUES = 'residues';

    const JSON_TOLERANCE_VALUE = 'fragTol';

    const JSON_TOLERANCE_UNIT = 'fragTolUnit';

    private $jobId;

    private $precursorId;

    private $fixedModifications = array();

    private $fragmentIons = array();

    private $peptides = array();

    private $fragmentTolerance;

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

    public function addFixedModification(Modification $modification)
    {
        $this->fixedModifications[$modification->getId()] = $modification;
    }

    public function addFragmentIon(FragmentIon $fragmentIon)
    {
        $this->fragmentIons[] = $fragmentIon;
    }

    public function addPeptide(Peptide $peptide)
    {
        $this->peptides[$peptide->getId()] = $peptide;
    }

    public function setFragmentTolerance(Tolerance $tolerance)
    {
        $this->fragmentTolerance = $tolerance;
    }

    public function getJobId()
    {
        return $this->jobId;
    }

    public function getPrecursorId()
    {
        return $this->precursorId;
    }

    public function getPeptide($id)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($id));
        }
        
        return $this->peptides[$id];
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
        return $this->fragmentTolerance->getTolerance();
    }

    public function getFragmentToleranceUnit()
    {
        return $this->fragmentTolerance->getUnit();
    }

    public function toJson($includeScore = false)
    {
        $data = array();
        $data[WorkUnit::JSON_JOB] = $this->jobId;
        $data[WorkUnit::JSON_PRECURSOR] = $this->precursorId;
        
        $data[WorkUnit::JSON_FRAGMENTS] = array();
        foreach ($this->fragmentIons as $fragment) {
            $data[WorkUnit::JSON_FRAGMENTS][] = array(
                'mz' => $fragment->getMz(),
                'intensity' => $fragment->getIntensity()
            );
        }
        
        // Reformat as we do not want the null score being sent
        $data[WorkUnit::JSON_PEPTIDES] = array();
        foreach ($this->peptides as $peptide) {
            $peptideData = array();
            $peptideData[WorkUnit::JSON_PEPTIDE_ID] = $peptide->getId();
            $peptideData[WorkUnit::JSON_PEPTIDE_SEQUENCE] = $peptide->getSequence();
            if ($includeScore) {
                $peptideData[WorkUnit::JSON_PEPTIDE_SCORE] = $peptide->getScore();
            }
            
            if ($peptide->isModified()) {
                $peptideData[WorkUnit::JSON_PEPTIDE_MODIFICATIONS] = array();
                
                foreach ($peptide->getModifications() as $modification) {
                    $mod = array();
                    $mod[WorkUnit::JSON_MODIFICATION_ID] = $modification->getId();
                    $mod[WorkUnit::JSON_MODIFICATION_MASS] = $modification->getMonoisotopicMass();
                    $mod[WorkUnit::JSON_MODIFICATION_RESIDUES] = implode('', $modification->getResidues());
                }
                
                $peptideData[WorkUnit::JSON_PEPTIDE_MODIFICATIONS][] = $mod;
            }
            
            $data[WorkUnit::JSON_PEPTIDES][] = $peptideData;
        }
        
        $data[WorkUnit::JSON_FIXED_MODIFICATIONS] = array();
        foreach ($this->fixedModifications as $modification) {
            $data[WorkUnit::JSON_FIXED_MODIFICATIONS][] = array(
                WorkUnit::JSON_MODIFICATION_ID => $modification->getId(),
                WorkUnit::JSON_MODIFICATION_MASS => $modification->getMonoisotopicMass(),
                WorkUnit::JSON_MODIFICATION_RESIDUES => implode('', $modification->getResidues())
            );
        }
        
        $data[WorkUnit::JSON_TOLERANCE_VALUE] = $this->getFragmentTolerance();
        $data[WorkUnit::JSON_TOLERANCE_UNIT] = $this->getFragmentToleranceUnit();
        
        return json_encode($data);
    }

    public static function fromJson($jsonStr)
    {
        $jsonObj = json_decode($jsonStr, true);
        
        if (is_null($jsonObj)) {
            throw new \InvalidArgumentException('Input must be a valid JSON string value.');
        }
        
        if (! isset($jsonObj[WorkUnit::JSON_JOB]) || ! is_int($jsonObj[WorkUnit::JSON_JOB])) {
            throw new \InvalidArgumentException(
                '"job" must be an int value. Valued passed is of type ' . gettype($jsonObj[WorkUnit::JSON_JOB]));
        }
        
        if (! isset($jsonObj[WorkUnit::JSON_PRECURSOR]) || ! is_int($jsonObj[WorkUnit::JSON_PRECURSOR])) {
            throw new \InvalidArgumentException(
                '"precursor" must be an int value. Valued passed is of type ' .
                     gettype($jsonObj[WorkUnit::JSON_PRECURSOR]));
        }
        
        // Initialise object
        $workUnit = new WorkUnit($jsonObj[WorkUnit::JSON_JOB], $jsonObj[WorkUnit::JSON_PRECURSOR]);
        
        // Parse fragment tolerance
        if (isset($jsonObj[WorkUnit::JSON_TOLERANCE_VALUE]) && isset($jsonObj[WorkUnit::JSON_TOLERANCE_UNIT])) {
            $workUnit->setFragmentTolerance(
                new Tolerance($jsonObj[WorkUnit::JSON_TOLERANCE_VALUE], $jsonObj[WorkUnit::JSON_TOLERANCE_UNIT]));
        }
        
        // Parse fixed modifications
        if (isset($jsonObj[WorkUnit::JSON_FIXED_MODIFICATIONS])) {
            foreach ($jsonObj[WorkUnit::JSON_FIXED_MODIFICATIONS] as $mod) {
                $residues = str_split($mod[WorkUnit::JSON_MODIFICATION_RESIDUES]);
                $workUnit->addFixedModification(
                    new Modification($mod[WorkUnit::JSON_MODIFICATION_ID], $mod[WorkUnit::JSON_MODIFICATION_MASS], 
                        $residues));
            }
        }
        
        // Parse peptides
        if (isset($jsonObj[WorkUnit::JSON_PEPTIDES])) {
            WorkUnit::fromJsonPeptides($jsonObj[WorkUnit::JSON_PEPTIDES], $workUnit);
        }
        
        // Parse fragments
        if (isset($jsonObj[WorkUnit::JSON_FRAGMENTS])) {
            foreach ($jsonObj[WorkUnit::JSON_FRAGMENTS] as $fragment) {
                $workUnit->addFragmentIon(new FragmentIon($fragment['mz'], $fragment['intensity']));
            }
        }
        return $workUnit;
    }

    private static function fromJsonPeptides(array $rawPeptides, WorkUnit $workUnit)
    {
        foreach ($rawPeptides as $rawPeptide) {
            if (! isset($rawPeptide[WorkUnit::JSON_PEPTIDE_ID]) || ! is_int($rawPeptide[WorkUnit::JSON_PEPTIDE_ID])) {
                throw new \InvalidArgumentException(
                    'A peptide "ID" must be an int value. Valued passed is of type ' .
                         gettype($rawPeptide[WorkUnit::JSON_PEPTIDE_ID]));
            }
            
            $peptide = new Peptide($rawPeptide[WorkUnit::JSON_PEPTIDE_ID]);
            
            if (isset($rawPeptide[WorkUnit::JSON_PEPTIDE_SEQUENCE])) {
                $peptide->setSequence($rawPeptide[WorkUnit::JSON_PEPTIDE_SEQUENCE]);
            }
            
            if (isset($rawPeptide[WorkUnit::JSON_PEPTIDE_SCORE]) && isset($rawPeptide[WorkUnit::JSON_PEPTIDE_IONS])) {
                $peptide->setScore($rawPeptide[WorkUnit::JSON_PEPTIDE_SCORE], $rawPeptide[WorkUnit::JSON_PEPTIDE_IONS]);
            }
            
            if (isset($rawPeptide[WorkUnit::JSON_PEPTIDE_MODIFICATIONS])) {
                foreach ($rawPeptide[WorkUnit::JSON_PEPTIDE_MODIFICATIONS] as $mod) {
                    $residues = str_split($mod[WorkUnit::JSON_MODIFICATION_RESIDUES]);
                    $peptide->addModification(
                        new Modification($mod[WorkUnit::JSON_MODIFICATION_ID], $mod[WorkUnit::JSON_MODIFICATION_MASS], 
                            $residues));
                }
            }
            
            $workUnit->addPeptide($peptide);
        }
    }
}
