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
namespace pgb_liv\crowdsource\Parallel\Slave;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\crowdsource\Core\WorkUnit;
use pgb_liv\crowdsource\Core\FragmentIon;
use pgb_liv\crowdsource\Core\Modification;
use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\php_ms\Core\Identification;
use pgb_liv\crowdsource\Parallel\Master\WorkUnitMaster;

/**
 * Accepts a Job ID and generates potential identification
 * candidates for the associated job spectra.
 *
 * @author Andrew Collins
 */
class WorkUnitSlave extends AbstractSlave
{

    const JOB_QUEUE_NAME = 'JobQueue';

    private $jobId;

    private $fastaId;

    private $massTolerance;

    /**
     *
     * @var Modification[]
     */
    private $modGroup;

    private $searchSet;

    private $jobChannel;

    private $fixedMods;

    private $sequenceLengthMin;

    private $sequenceLengthMax;

    public function __construct(\ADOConnection $conn)
    {
        parent::__construct($conn, WorkUnitMaster::WORKUNIT_QUEUE_NAME);
    }

    private function processPrecursor(array $job)
    {
        if ($job['job'] != $this->jobId) {
            $this->jobId = $job['job'];
            $this->initialise2();
        }

        $precursor = $job['precursor'];
        $mass = $job['mass'];
        $charge = $job['charge'];

        // Unmodified
        $peptides = $this->getPeptides($mass);

        $candidates = array();
        foreach ($peptides as $peptide) {
            $candidates[] = array(
                $peptide
            );
        }

        // Modified
        foreach ($this->searchSet as $set) {
            $additiveMass = 0;
            $residues = array();
            foreach ($set as $modId) {
                $additiveMass += $this->modGroup[$modId]->getMonoisotopicMass();
                if ($this->modGroup[$modId]->getPosition() != Modification::POSITION_NTERM &&
                    $this->modGroup[$modId]->getPosition() != Modification::POSITION_CTERM) {
                    $idx = '`' . strtolower(implode('_count` + `', $this->modGroup[$modId]->getResidues())) . '_count`';
                } else {
                    $idx = '1';
                }

                if (! isset($residues[$idx])) {
                    $residues[$idx] = 0;
                }

                $residues[$idx] ++;
            }

            $peptides = $this->getModifiablePeptides($mass - $additiveMass, $residues);

            if (count($peptides) === 0) {
                continue;
            }

            foreach ($peptides as $peptide) {
                $candidates[] = array(
                    $peptide,
                    $set
                );
            }
        }

        if (count($candidates) > 0) {
            $this->createWorkUnits($precursor, $candidates, $charge);
        }
    }

    private function getFragmentIons($precursorId)
    {
        $fragments = array();
        $results = $this->adodb->Execute(
            'SELECT `mz`, `intensity` FROM `raw_ms2` WHERE `job` = ' . $this->jobId . ' && `ms1` = ' . $precursorId);

        foreach ($results as $fragment) {
            $fragments[] = new FragmentIon((float) $fragment['mz'], (float) $fragment['intensity']);
        }

        return $fragments;
    }

    private function getFixedMods()
    {
        $rs = $this->adodb->Execute(
            'SELECT `job_fixed_mod`.`mod_id`, `unimod_modifications`.`mono_mass`, `job_fixed_mod`.`acid` FROM `job_fixed_mod`
    INNER JOIN `unimod_modifications` ON `unimod_modifications`.`record_id` = `job_fixed_mod`.`mod_id` WHERE
            `job_fixed_mod`.`job` = ' . $this->jobId);

        $mods = array();
        foreach ($rs as $record) {
            $mods[] = new Modification((int) $record['mod_id'], (float) $record['mono_mass'], array(
                $record['acid']
            ));
        }

        return $mods;
    }

    private function createWorkUnits($precursorId, array $candidates, $charge)
    {
        echo $precursorId . '-' . count($candidates) . PHP_EOL;

        $uid = $this->jobId . '-' . $precursorId;

        $packetNum = 0;
        $workUnit = new WorkUnit($uid . '-' . $packetNum);
        $workUnit->setCharge($charge);

        $fragmentIons = $this->getFragmentIons($precursorId);

        $workUnit->setFragmentTolerance($this->massTolerance);
        $this->injectFragmentIons($workUnit, $fragmentIons);
        $this->injectFixedModifications($workUnit, $this->fixedMods);

        $totalSequenceLength = 0;
        $workUnits = array();
        $candidateCount = 0;
        foreach ($candidates as $candidate) {
            $peptide = new Peptide((int) $candidate[0]['id']);

            if (isset($candidate[1])) {
                foreach ($candidate[1] as $modId) {
                    $peptide->addModification($this->modGroup[$modId]);
                }
            }

            $peptide->setSequence($candidate[0]['sequence']);
            $identification = new Identification();
            $identification->setSequence($peptide);
            $workUnit->addIdentification($identification);

            $totalSequenceLength += $peptide->getLength();
            $candidateCount ++;
            if ($candidateCount > 50 || $totalSequenceLength > 400) {
                $workUnits[$workUnit->getUid()] = $workUnit->toJson();
                $workUnit->clearIdentifications();

                $candidateCount = 0;
                $totalSequenceLength = 0;
                $packetNum ++;
                $workUnit->setUid($uid . '-' . $packetNum);
            }
        }

        if (count($workUnit->getIdentifications()) > 0) {
            $workUnits[$workUnit->getUid()] = $workUnit->toJson();
        }

        foreach ($workUnits as $uid => $workUnit) {
            $msg = new AMQPMessage(gzencode($workUnit, 5));
            $this->jobChannel->batch_basic_publish($msg, '', self::JOB_QUEUE_NAME);
            echo 'Added ' . $uid . PHP_EOL;
        }

        $this->adodb->Execute(
            'UPDATE `job_queue` SET `workunits_created` = `workunits_created` + ' . count($workUnits) . ' WHERE `id` =' .
            $this->jobId);

        $this->jobChannel->publish_batch();
    }

    protected function injectFixedModifications(WorkUnit $workUnit, array $modifications)
    {
        foreach ($modifications as $modification) {
            $workUnit->addFixedModification($modification);
        }
    }

    protected function injectFragmentIons(WorkUnit $workUnit, $fragmentIons)
    {
        foreach ($fragmentIons as $fragmentIon) {
            $workUnit->addFragmentIon($fragmentIon);
        }
    }

    protected function initialise2()
    {
        $jobProperties = $this->adodb->GetRow(
            'SELECT `fasta`.`id`, `peptide_min`, `peptide_max` FROM `job_queue` LEFT JOIN `fasta` ON `fasta`.`hash` = `job_queue`.`database_hash` WHERE `job_queue`.`id` = ' .
            $this->jobId);

        $this->fastaId = $jobProperties['id'];
        $this->sequenceLengthMin = $jobProperties['peptide_min'];
        $this->sequenceLengthMax = $jobProperties['peptide_max'];

        $toleranceRaw = $this->adodb->GetRow(
            'SELECT `precursor_tolerance`, `precursor_tolerance_unit` FROM `job_queue` WHERE `id` = ' . $this->jobId);

        $this->massTolerance = new Tolerance((float) $toleranceRaw['precursor_tolerance'],
            $toleranceRaw['precursor_tolerance_unit']);

        // Get variable mods
        $modifications = $this->adodb->Execute(
            'SELECT `j`.`mod_id`, `acid`, `mono_mass` FROM `job_variable_mod` `j` LEFT JOIN `unimod_modifications` ON `j`.`mod_id` = `record_id` WHERE `j`.`job` = ' .
            $this->jobId);

        $this->fixedMods = $this->getFixedMods();

        // Indexed
        $this->modGroup = array();
        foreach ($modifications as $modification) {
            $modId = (int) $modification['mod_id'];

            if (! isset($this->modGroup[$modId])) {
                $this->modGroup[$modId] = new Modification($modId);
                $this->modGroup[$modId]->setMonoisotopicMass((float) $modification['mono_mass']);
            }

            if ($modification['acid'] == '[') {
                $this->modGroup[$modId]->setPosition(Modification::POSITION_NTERM);
            } elseif ($modification['acid'] == ']') {
                $this->modGroup[$modId]->setPosition(Modification::POSITION_CTERM);
            } else {
                $residues = $this->modGroup[$modId]->getResidues();
                array_push($residues, $modification['acid']);
                $residues = $this->modGroup[$modId]->setResidues($residues);
            }
        }

        $set = array();
        foreach ($this->modGroup as $modId => $modification) {
            for ($i = 0; $i < MAX_MOD_PER_TYPE; $i ++) {
                $set[] = $modId;
            }
        }

        // Generate set of possible mods
        $powerSet = $this->powerSet($set);
        $this->searchSet = array();

        // Remove empty set, set larger than MAX_MOD_TOTAL and duplicates
        foreach ($powerSet as $set) {
            if (count($set) > MAX_MOD_TOTAL || count($set) == 0) {
                continue;
            }

            sort($set);

            $isFound = false;
            foreach ($this->searchSet as $search) {
                $isFound = $this->isArrayEqual($set, $search);
                if ($isFound) {
                    break;
                }
            }

            if ($isFound) {
                continue;
            }

            $this->searchSet[] = $set;
        }
    }

    function isArrayEqual($a, $b)
    {
        if (count($a) != count($b)) {
            return false;
        }

        $isEqual = true;
        for ($i = 0; $i < count($a); $i ++) {
            if ($a[$i] != $b[$i]) {
                $isEqual = false;
                break;
            }
        }

        return $isEqual;
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
        // TODO: Validate peptide is acceptable length
        $tolerance = $this->massTolerance->getDaltonDelta($spectraMass);
        $pepMassLow = $spectraMass - $tolerance;
        $pepMassHigh = $spectraMass + $tolerance;

        return $this->adodb->GetAll(
            'SELECT `f`.`peptide` AS `id`, `p`.`peptide` AS `sequence` FROM `fasta_peptide_fixed` `f` LEFT JOIN `fasta_peptides` `p` ON `f`.`peptide` = `p`.`id` && `fasta` = ' .
            $this->fastaId . ' WHERE `job` = ' . $this->jobId . ' && `length` BETWEEN ' . $this->sequenceLengthMin .
            ' AND ' . $this->sequenceLengthMax . ' && `fixed_mass` BETWEEN ' . $pepMassLow . ' AND ' . $pepMassHigh);
    }

    /**
     * Get the peptides that have a mass matching the spectra mass and apply where
     *
     * @param float $spectraMass
     *            Mass of the spectra to match against
     * @return array Peptide IDs within tolerance
     */
    private function getModifiablePeptides($spectraMass, $where)
    {
        // TODO: Validate peptide is acceptable length
        $tolerance = $this->massTolerance->getDaltonDelta($spectraMass);
        $pepMassLow = $spectraMass - $tolerance;
        $pepMassHigh = $spectraMass + $tolerance;

        $query = 'SELECT `f`.`peptide` AS `id`, `p`.`peptide` AS `sequence` FROM `fasta_peptide_fixed` `f`
            LEFT JOIN `fasta_peptides` `p` ON `f`.`peptide` = `p`.`id` && `fasta` = ' . $this->fastaId .
            '
            WHERE `job` = ' . $this->jobId . '&& `length` BETWEEN ' . $this->sequenceLengthMin . ' AND ' .
            $this->sequenceLengthMax . ' && `fixed_mass` BETWEEN ' . $pepMassLow . ' AND ' . $pepMassHigh;

        foreach ($where as $key => $value) {
            $query .= ' && ' . $key . ' >= ' . $value;
        }

        return $this->adodb->GetAll($query);
    }

    private function powerSet($array)
    {
        // add the empty set
        $results = array(
            array()
        );

        foreach ($array as $element) {
            foreach ($results as $combination) {
                $results[] = array_merge(array(
                    $element
                ), $combination);
            }
        }

        return $results;
    }

    protected function initialise()
    {
        $this->jobChannel = $this->amqpConnection->channel();
        $this->jobChannel->queue_declare(self::JOB_QUEUE_NAME, false, false, false, false);
    }

    protected function finalise()
    {
        $this->jobChannel->close();
    }

    public function processJob($message)
    {
        $object = json_decode($message->body, true);

        $this->processPrecursor($object);

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }
}
