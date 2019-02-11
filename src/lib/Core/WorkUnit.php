<?php
/**
 * Copyright 2019 University of Liverpool
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

    const JSON_HOSTNAME = 'hostname';

    const JSON_USER = 'user';

    const JSON_BYTES_SENT = 'bytesSent';

    const JSON_BYTES_RECEIVED = 'bytesReceived';

    const JSON_PROCESS_TIME = 'processTime';

    const JSON_CHARGE = 'z';

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

    /**
     * Hostname running the parasite
     *
     * @var string
     */
    private $hostname;

    /**
     * The IP of the user running the parasite
     *
     * @var int
     */
    private $user;

    /**
     * The amount of bytes sent out
     *
     * @var int
     */
    private $bytesSent;

    /**
     * The amount of bytes received
     *
     * @var int
     */
    private $bytesReceived;

    /**
     * The process time of the job
     *
     * @var float
     */
    private $processTime;

    /**
     * Charge value
     *
     * @var int
     */
    private $charge;

    public function __construct($uid)
    {
        $this->setUid($uid);
    }

    public function setCharge($charge)
    {
        $this->charge = (int) $charge;
    }

    public function getCharge()
    {
        return $this->charge;
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

    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setBytesSent($bytes)
    {
        $this->bytesSent = $bytes;
    }

    public function getBytesSent()
    {
        return $this->bytesSent;
    }

    public function setBytesReceived($bytes)
    {
        $this->bytesReceived = $bytes;
    }

    public function getBytesReceived()
    {
        return $this->bytesReceived;
    }

    public function setProcessTime($processTime)
    {
        $this->processTime = $processTime;
    }

    public function getProcessTime()
    {
        return $this->processTime;
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

        $data[WorkUnit::JSON_CHARGE] = $this->charge;
        
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

        // Parse meta
        if (isset($jsonObj[WorkUnit::JSON_HOSTNAME])) {
            $workUnit->setHostname($jsonObj[WorkUnit::JSON_HOSTNAME]);
        }

        if (isset($jsonObj[WorkUnit::JSON_USER])) {
            $workUnit->setUser($jsonObj[WorkUnit::JSON_USER]);
        }

        if (isset($jsonObj[WorkUnit::JSON_BYTES_SENT])) {
            $workUnit->setBytesSent($jsonObj[WorkUnit::JSON_BYTES_SENT]);
        }

        if (isset($jsonObj[WorkUnit::JSON_BYTES_RECEIVED])) {
            $workUnit->setBytesReceived($jsonObj[WorkUnit::JSON_BYTES_RECEIVED]);
        }

        if (isset($jsonObj[WorkUnit::JSON_PROCESS_TIME])) {
            $workUnit->setProcessTime($jsonObj[WorkUnit::JSON_PROCESS_TIME]);
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
