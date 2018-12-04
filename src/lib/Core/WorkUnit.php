<?php
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
namespace pgb_liv\crowdsource\Core;

use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Core\Identification;

class WorkUnit
{

    const JSON_UID = 'uid';

    const JSON_PEPTIDES = 'peptides';

    const JSON_FRAGMENTS = 'fragments';

    const JSON_FIXED_MODIFICATIONS = 'fixedMods';

    const JSON_TOLERANCE_VALUE = 'fragTol';

    const JSON_TOLERANCE_UNIT = 'fragTolUnit';

    private $uid;

    private $fixedModifications = array();

    /**
     *
     * @var FragmentIon[]
     */
    private $fragmentIons = array();

    /**
     * List of identifications associated with this instance
     *
     * @var Identification[]
     */
    private $identification = array();

    private $fragmentTolerance;

    public function __construct($uid)
    {
        $this->setUid($uid);
    }

    public function addFixedModification(Modification $modification)
    {
        $this->fixedModifications[$modification->getId()] = $modification;
    }

    public function addFragmentIon(FragmentIon $fragmentIon)
    {
        $this->fragmentIons[] = $fragmentIon;
    }

    public function addIdentification(Identification $identification)
    {
        $this->identification[] = $identification;
    }

    public function clearIdentifications()
    {
        $this->identification = array();
    }

    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    public function setFragmentTolerance(Tolerance $tolerance)
    {
        $this->fragmentTolerance = $tolerance;
    }

    public function getUid()
    {
        return $this->uid;
    }

    /**
     *
     * @deprecated Do not use, not safe
     * @param int $id
     * @throws \InvalidArgumentException
     * @return \pgb_liv\crowdsource\Core\Peptide[]
     */
    public function getPeptide($id)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($id));
        }

        foreach ($this->getPeptides() as $peptide) {
            if ($peptide->getId() == $id) {
                return $peptide;
            }
        }

        return null;
    }

    /**
     * Gets the set of peptides stored by this workunit
     *
     * @return Identification[]
     */
    public function getIdentifications()
    {
        return $this->identification;
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

    public function toJson()
    {
        $data = array();
        $data[WorkUnit::JSON_UID] = $this->uid;

        $data[WorkUnit::JSON_FRAGMENTS] = array();
        foreach ($this->fragmentIons as $fragment) {
            $data[WorkUnit::JSON_FRAGMENTS][] = $fragment->toArray();
        }

        $data[WorkUnit::JSON_PEPTIDES] = array();
        foreach ($this->identification as $identification) {
            $data[WorkUnit::JSON_PEPTIDES][] = $identification->getSequence()->toArray();
        }

        $data[WorkUnit::JSON_FIXED_MODIFICATIONS] = array();
        foreach ($this->fixedModifications as $modification) {
            $data[WorkUnit::JSON_FIXED_MODIFICATIONS][] = $modification->toArray();
        }

        $data[WorkUnit::JSON_TOLERANCE_VALUE] = $this->getFragmentTolerance();
        $data[WorkUnit::JSON_TOLERANCE_UNIT] = $this->getFragmentToleranceUnit();

        return json_encode($data);
    }

    /**
     * Returns a workunit from the supplied JSON string
     *
     * @param string $jsonStr
     * @throws \InvalidArgumentException
     * @return WorkUnit
     */
    public static function fromJson($jsonStr)
    {
        $jsonObj = json_decode($jsonStr, true);

        if (is_null($jsonObj)) {
            throw new \InvalidArgumentException('Input must be a valid JSON string value.');
        }

        if (! isset($jsonObj[WorkUnit::JSON_UID])) {
            throw new \InvalidArgumentException(
                '"UID" not found. Valued passed is of type ' . gettype($jsonObj[WorkUnit::JSON_UID]));
        }

        // Initialise object
        $workUnit = new WorkUnit($jsonObj[WorkUnit::JSON_UID]);

        // Parse fragment tolerance
        if (isset($jsonObj[WorkUnit::JSON_TOLERANCE_VALUE]) && isset($jsonObj[WorkUnit::JSON_TOLERANCE_UNIT])) {
            $workUnit->setFragmentTolerance(
                new Tolerance($jsonObj[WorkUnit::JSON_TOLERANCE_VALUE], $jsonObj[WorkUnit::JSON_TOLERANCE_UNIT]));
        }

        // Parse fixed modifications
        if (isset($jsonObj[WorkUnit::JSON_FIXED_MODIFICATIONS])) {
            foreach ($jsonObj[WorkUnit::JSON_FIXED_MODIFICATIONS] as $modArray) {
                $workUnit->addFixedModification(Modification::fromArray($modArray));
            }
        }

        // Parse peptides
        if (isset($jsonObj[WorkUnit::JSON_PEPTIDES])) {
            WorkUnit::fromJsonPeptides($jsonObj[WorkUnit::JSON_PEPTIDES], $workUnit);
        }

        // Parse fragments
        if (isset($jsonObj[WorkUnit::JSON_FRAGMENTS])) {
            foreach ($jsonObj[WorkUnit::JSON_FRAGMENTS] as $fragment) {
                $workUnit->addFragmentIon(FragmentIon::fromArray($fragment));
            }
        }

        return $workUnit;
    }

    private static function fromJsonPeptides(array $rawPeptides, WorkUnit $workUnit)
    {
        foreach ($rawPeptides as $rawPeptide) {
            $workUnit->addIdentification(Peptide::fromArray($rawPeptide));
        }
    }
}
