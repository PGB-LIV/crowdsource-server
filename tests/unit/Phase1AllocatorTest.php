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
namespace pgb_liv\crowdsource\Test\Unit;

use pgb_liv\crowdsource\Allocator\Phase1Allocator;
use pgb_liv\crowdsource\Core\Phase1WorkUnit;

class Phase1AllocatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        global $adodb;
        
        $allocator = new Phase1Allocator($adodb, 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Allocator\Phase1Allocator', $allocator);
        
        return $allocator;
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments()
    {
        global $adodb;
        
        $allocator = new Phase1Allocator($adodb, 'fail');
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFragmentIons
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanGetWorkUnit()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanGetWorkUnitNoJob()
    {
        global $adodb;
        
        $this->cleanUp();
        
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals(false, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFragmentIons
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::setWorkUnitWorker
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::recordWorkUnitWorker
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanAssignWorkUnitValidId()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $allocator->setWorkUnitWorker(2130706433, $workUnit);
        
        $record = $adodb->GetRow(
            'SELECT `status`, `assigned_to` FROM `workunit1` WHERE `job` = ' . $workUnit->getJobId() . ' && `ms1` = ' .
                 $workUnit->getPrecursorId());
        
        $this->assertEquals('ASSIGNED', $record['status']);
        $this->assertEquals('2130706433', $record['assigned_to']);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFragmentIons
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::setWorkUnitWorker
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::recordWorkUnitWorker
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanAssignWorkUnitInvalidID()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $allocator->setWorkUnitWorker('fail', $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFragmentIons
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::setWorkUnitWorker
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::recordWorkUnitWorker
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::isPhaseComplete
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanReassignWorkUnit()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $allocator->setWorkUnitWorker(0, $workUnit);
        
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals(false, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFragmentIons
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::setWorkUnitResults
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::recordPeptideScores
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptideScore
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanSetResults()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        $this->assertEquals($testUnit, $workUnit);
        
        $workUnit->addPeptideScore(0, 250.9);
        $workUnit->addPeptideScore(1, 0.0);
        
        $allocator->setWorkUnitResults($workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setPhase
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setWorkUnitKeys
     * @covers pgb_liv\crowdsource\Allocator\AbstractAllocator::setJobDone
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::injectFragmentIons
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::setWorkUnitResults
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::recordPeptideScores
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptideScore
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanSetJobDone()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        $this->assertEquals($testUnit, $workUnit);
        
        $workUnit->addPeptideScore(0, 250.9);
        $workUnit->addPeptideScore(1, 0.0);
        
        $allocator->setWorkUnitResults($workUnit);
        
        $workUnit = $allocator->getWorkUnit();
        $this->assertEquals(false, $workUnit);
        
        $this->cleanUp();
    }

    private function getPeptides()
    {
        $peptides = array();
        $peptides[] = array(
            'structure' => 'PEPTIDE',
            'score' => null
        );
        $peptides[] = array(
            'structure' => 'PEPTIDER',
            'score' => null
        );
        $peptides[] = array(
            'structure' => 'PEPTIDEK',
            'score' => null
        );
        
        return $peptides;
    }

    private function getMs2()
    {
        $ms2 = array();
        
        $ms2[0] = array(
            'mz' => 100.5,
            'intensity' => 600.5
        );
        $ms2[1] = array(
            'mz' => 200.5,
            'intensity' => 500.5
        );
        $ms2[2] = array(
            'mz' => 300.5,
            'intensity' => 400.5
        );
        $ms2[3] = array(
            'mz' => 400.5,
            'intensity' => 300.5
        );
        $ms2[4] = array(
            'mz' => 500.5,
            'intensity' => 200.5
        );
        $ms2[5] = array(
            'mz' => 600.5,
            'intensity' => 100.5
        );
        
        return $ms2;
    }

    private function getFixedModifications()
    {
        $modifications = array();
        $modifications[0] = array(
            'id' => 4,
            'mass' => 57.021464,
            'residue' => 'C'
        );
        
        return $modifications;
    }

    private function createJob($jobId, $phase)
    {
        global $adodb;
        
        $adodb->Execute(
            'INSERT INTO `job_queue` (`id`, `phase`, `state`, `fragment_tolerance`, `fragment_tolerance_unit`) VALUES (' .
                 $jobId . ', \'' . $phase . '\', \'READY\', 10, \'ppm\');');
    }

    private function createWorkUnit($jobId, $precursorId)
    {
        global $adodb;
        
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $workUnit->setFragmentTolerance(10.0, 'ppm');
        
        $adodb->Execute('INSERT INTO `workunit1` (`job`, `ms1`) VALUES (' . $jobId . ', ' . $precursorId . ');');
        
        $ms2 = $this->getMs2();
        foreach ($ms2 as $key => $value) {
            $workUnit->addFragmentIon($value['mz'], $value['intensity']);
            $adodb->Execute(
                'INSERT INTO `raw_ms2` (`job`, `ms1`, `id`, `mz`, `intensity`) VALUES (' . $jobId . ', ' . $precursorId .
                     ', ' . $key . ', ' . $value['mz'] . ', ' . $value['intensity'] . ');');
        }
        
        $peptides = $this->getPeptides();
        foreach ($peptides as $id => $peptide) {
            $workUnit->addPeptide($id, $peptide['structure']);
            $adodb->Execute(
                'INSERT INTO `workunit1_peptides` (`job`, `ms1`, `peptide`) VALUES (' . $jobId . ', ' . $precursorId .
                     ', ' . $id . ');');
            $adodb->Execute(
                'INSERT INTO `fasta_peptides` (`job`, `id`, `peptide`) VALUES (' . $jobId . ', ' . $id . ', ' .
                     $adodb->quote($peptide['structure']) . ');');
        }
        
        $modifications = $this->getFixedModifications();
        foreach ($modifications as $modification) {
            $workUnit->addFixedModification($modification['mass'], $modification['residue']);
            $adodb->Execute(
                'INSERT INTO `job_fixed_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', ' . $modification['id'] .
                     ', ' . $adodb->quote($modification['residue']) . ');');
        }
        
        return $workUnit;
    }

    private function cleanUp()
    {
        global $adodb;
        
        $adodb->Execute('TRUNCATE `fasta_peptides`');
        $adodb->Execute('TRUNCATE `raw_ms2`');
        $adodb->Execute('TRUNCATE `workunit1`');
        $adodb->Execute('TRUNCATE `workunit1_peptides`');
        $adodb->Execute('TRUNCATE `job_fixed_mod`');
        $adodb->Execute('TRUNCATE `job_queue`');
    }
}