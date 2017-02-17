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

use pgb_liv\crowdsource\Core\WorkUnit;
use pgb_liv\crowdsource\Allocator\WorkUnitAllocator;
use pgb_liv\crowdsource\Core\Tolerance;
use pgb_liv\crowdsource\Core\FragmentIon;
use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\crowdsource\Core\PeptideModification;

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
        $this->cleanUp();
        
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
        $this->cleanUp();
        
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
        $this->cleanUp();
        
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
        $this->cleanUp();
        
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
        $this->cleanUp();
        
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
     * @covers pgb_liv\crowdsource\Core\WorkUnit::toJson
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanRecordResults()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $peptide = $workUnit->getPeptide(0);
        $peptide->setScore(120.6, 8);
        
        $peptide = $workUnit->getPeptide(1);
        $peptide->setScore(46.16, 5);
        
        $peptide = $workUnit->getPeptide(2);
        $peptide->setScore(25.92, 2);
        
        $isSuccess = $allocator->recordResults($workUnit->toJson(true));
        $this->assertEquals(true, $isSuccess);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::recordResults
     * @covers pgb_liv\crowdsource\Core\WorkUnit::toJson
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanRecordResultsNoJob()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $peptide = $workUnit->getPeptide(0);
        $peptide->setScore(120.6, 8);
        
        $peptide = $workUnit->getPeptide(1);
        $peptide->setScore(46.16, 5);
        
        $peptide = $workUnit->getPeptide(2);
        $peptide->setScore(25.92, 2);
        
        $this->cleanUp();
        $isSuccess = $allocator->recordResults($workUnit->toJson(true));
        $this->assertEquals(false, $isSuccess);
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\WorkUnitAllocator::recordResults
     * @covers pgb_liv\crowdsource\Core\WorkUnit::toJson
     *
     * @uses pgb_liv\crowdsource\Allocator\WorkUnitAllocator
     */
    public function testObjectCanRecordResultsUnknownPhase()
    {
        global $adodb;
        $this->cleanUp();
        
        $this->createJob(1, 1);
        $testUnit = $this->createWorkUnit(1, 1);
        $allocator = new WorkUnitAllocator($adodb);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals($testUnit, $workUnit);
        
        $peptide = $workUnit->getPeptide(0);
        $peptide->setScore(120.6, 8);
        
        $peptide = $workUnit->getPeptide(1);
        $peptide->setScore(46.16, 5);
        
        $peptide = $workUnit->getPeptide(2);
        $peptide->setScore(25.92, 2);
        
        $this->cleanUp();
        $this->createJob(1, 127);
        $isSuccess = $allocator->recordResults($workUnit->toJson(true));
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
        
        $workUnit = new WorkUnit($jobId, $precursorId);
        $workUnit->setFragmentTolerance(new Tolerance(10.0, 'ppm'));
        
        $adodb->Execute('INSERT INTO `workunit1` (`job`, `ms1`) VALUES (' . $jobId . ', ' . $precursorId . ');');
        
        $ms2 = $this->getMs2();
        foreach ($ms2 as $key => $value) {
            $workUnit->addFragmentIon(new FragmentIon($value['mz'], $value['intensity']));
            $adodb->Execute(
                'INSERT INTO `raw_ms2` (`job`, `ms1`, `id`, `mz`, `intensity`) VALUES (' . $jobId . ', ' . $precursorId .
                     ', ' . $key . ', ' . $value['mz'] . ', ' . $value['intensity'] . ');');
        }
        
        $peptides = $this->getPeptides();
        foreach ($peptides as $id => $peptide) {
            $pep = new Peptide($id);
            $pep->setSequence($peptide['structure']);
            $workUnit->addPeptide($pep);
            $adodb->Execute(
                'INSERT INTO `workunit1_peptides` (`job`, `ms1`, `peptide`) VALUES (' . $jobId . ', ' . $precursorId .
                     ', ' . $id . ');');
            $adodb->Execute(
                'INSERT INTO `fasta_peptides` (`job`, `id`, `peptide`) VALUES (' . $jobId . ', ' . $id . ', ' .
                     $adodb->quote($peptide['structure']) . ');');
        }
        
        $modifications = $this->getFixedModifications();
        foreach ($modifications as $modification) {
            $mod = new PeptideModification(4, $modification['mass'], 
                array(
                    $modification['residue']
                ));
            $workUnit->addFixedModification($mod);
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
            'INSERT INTO `job_queue` (`id`, `phase`, `state`, `fragment_tolerance`, `fragment_tolerance_unit`) VALUES (' .
                 $jobId . ', \'' . $phase . '\', \'READY\', 10, \'ppm\');');
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
