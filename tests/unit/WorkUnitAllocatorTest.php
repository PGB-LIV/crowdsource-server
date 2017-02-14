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

use pgb_liv\crowdsource\Core\Phase1WorkUnit;
use pgb_liv\crowdsource\Allocator\WorkUnitAllocator;

class WorkUnitAllocatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        global $adodb;
        
        $allocator = new WorkUnitAllocator($adodb);
        $this->assertInstanceOf('pgb_liv\crowdsource\Allocator\WorkUnitAllocator', $allocator);
        
        return $allocator;
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanGetWorkUnitPhase1()
    {
        global $adodb;
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanGetWorkUnitPhase1ForgedRemoteAddr()
    {
        global $adodb;
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanGetWorkUnitNoJob()
    {
        global $adodb;
        
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals(false, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanGetWorkUnitUnknownPhase()
    {
        global $adodb;
        
        $this->createJob(1, 127);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals(false, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanGetWorkUnitNoWorkUnit()
    {
        global $adodb;
        
        $this->createJob(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals(false, $workUnit);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::recordResults
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanRecordResults()
    {
        global $adodb;
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $workUnit->addPeptideScore(0, 120.6);
        $workUnit->addPeptideScore(1, 46.16);
        $workUnit->addPeptideScore(0, 25.92);
        
        $isSuccess = $allocator->recordResults($workUnit);
        $this->assertEquals(true, $isSuccess);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::recordResults
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanRecordResultsNoJob()
    {
        global $adodb;
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $workUnit->addPeptideScore(0, 120.6);
        $workUnit->addPeptideScore(1, 46.16);
        $workUnit->addPeptideScore(0, 25.92);
        
        $this->cleanUp();
        $isSuccess = $allocator->recordResults($workUnit);
        $this->assertEquals(false, $isSuccess);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::recordResults
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanRecordResultsUnknownPhase()
    {
        global $adodb;
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $workUnit->addPeptideScore(0, 120.6);
        $workUnit->addPeptideScore(1, 46.16);
        $workUnit->addPeptideScore(0, 25.92);
        
        $this->cleanUp();
        $this->createJob(1, 127);
        $isSuccess = $allocator->recordResults($workUnit);
        $this->assertEquals(false, $isSuccess);
        
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

    private function createWorkUnit($jobId, $precursorId)
    {
        global $adodb;
        
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
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

    private function createJob($jobId, $phase)
    {
        global $adodb;
        
        $adodb->Execute(
            'INSERT INTO `job_queue` (`id`, `phase`, `state`) VALUES (' . $jobId . ', \'' . $phase . '\', \'READY\');');
    }

    private function cleanUp()
    {
        global $adodb;
        
        $adodb->Execute('TRUNCATE `fasta_peptides`');
        $adodb->Execute('TRUNCATE `raw_ms2`');
        $adodb->Execute('TRUNCATE `workunit1`');
        $adodb->Execute('TRUNCATE `workunit1_peptides`');
        $adodb->Execute('TRUNCATE `job_queue`');
        $adodb->Execute('TRUNCATE `job_fixed_mod`');
    }
}
