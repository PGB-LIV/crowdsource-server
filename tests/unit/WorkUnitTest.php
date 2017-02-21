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
use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\crowdsource\Core\FragmentIon;
use pgb_liv\crowdsource\Core\Tolerance;
use pgb_liv\crowdsource\Core\Modification;

class WorkUnitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $workUnit = new WorkUnit(1, 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\WorkUnit', $workUnit);
        
        return $workUnit;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments1()
    {
        $workUnit = new WorkUnit('fail', 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\WorkUnit', $workUnit);
        
        return $workUnit;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments2()
    {
        $workUnit = new WorkUnit(1, 'fail');
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\WorkUnit', $workUnit);
        
        return $workUnit;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getJobId
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getPrecursorId
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetConstructorArgs()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        
        $this->assertEquals($jobId, $workUnit->getJobId());
        $this->assertEquals($precursorId, $workUnit->getPrecursorId());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::addFixedModification
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFixedModifications
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidFixedModification()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        
        $mods = array();
        $mods[25] = new Modification(25, 79.97, array(
            'C'
        ));
        $workUnit->addFixedModification($mods[25]);
        
        $this->assertEquals($mods, $workUnit->getFixedModifications());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::addFragmentIon
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFragmentIons
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidFragmentIon()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        
        $fragments = array();
        $fragments[0] = new FragmentIon(79.97, 150.5);
        $workUnit->addFragmentIon($fragments[0]);
        
        $this->assertEquals($fragments, $workUnit->getFragmentIons());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::addPeptide
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getPeptides
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getPeptide
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidPeptide()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = new Peptide(0);
        $peptides[0]->setSequence('PEPTIDE');
        
        $workUnit->addPeptide($peptides[0]);
        
        $this->assertEquals($peptides, $workUnit->getPeptides());
        $this->assertEquals($peptides[0], $workUnit->getPeptide(0));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getPeptide
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetInvalidPeptide()
    {
        $jobId = 1;
        $precursorId = 2;
        
        $workUnit = new WorkUnit($jobId, $precursorId);
        $workUnit->getPeptide('fail');
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::setFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFragmentToleranceUnit
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidFragmentTolerance1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        $fragTol = 10.0;
        $fragUnit = 'ppm';
        $workUnit->setFragmentTolerance(new Tolerance($fragTol, $fragUnit));
        
        $this->assertEquals($fragTol, $workUnit->getFragmentTolerance());
        $this->assertEquals($fragUnit, $workUnit->getFragmentToleranceUnit());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::toJson
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetValidJsonFromWorkUnit()
    {
        $json = '{"job":1,"precursor":2,"fragments":[{"mz":79.97,"intensity":150.5}],"peptides":[{"id":512,"sequence":"PEPTIDE"},{"id":213,"sequence":"PEPTIDER"},{"id":0,"sequence":"PEPTIDEK"}],"fixedMods":[{"id":4,"mass":79.97,"residues":"C"}],"fragTol":0.05,"fragTolUnit":"da"}';
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        $fragTol = 0.05;
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance(new Tolerance($fragTol, $fragUnit));
        
        $mods = array();
        $mods[4] = new Modification(4, 79.97, array(
            'C'
        ));
        $workUnit->addFixedModification($mods[4]);
        
        $fragments = array();
        $fragments[] = new FragmentIon(79.97, 150.5);
        $workUnit->addFragmentIon($fragments[0]);
        
        $peptides = array();
        $peptides[512] = new Peptide(512);
        $peptides[512]->setSequence('PEPTIDE');
        $peptides[213] = new Peptide(213);
        $peptides[213]->setSequence('PEPTIDER');
        $peptides[0] = new Peptide(0);
        $peptides[0]->setSequence('PEPTIDEK');
        
        $workUnit->addPeptide($peptides[512]);
        $workUnit->addPeptide($peptides[213]);
        $workUnit->addPeptide($peptides[0]);
        
        $this->assertEquals($json, $workUnit->toJson());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJson
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJsonPeptides
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetValidWorkUnitFromJson1()
    {
        $json = '{"job":1,"precursor":2,"fragments":[{"mz":79.97,"intensity":150.5}],"peptides":[{"id":512,"sequence":"PEPTIDE","score":120.6,"ionsMatched":5},{"id":213,"sequence":"PEPTIDER","score":23.6,"ionsMatched":9},{"id":0,"sequence":"PEPTIDEK"}],"fixedMods":[{"id":21,"mass":79.97,"residues":"C"}],"fragTol":0.05,"fragTolUnit":"da"}';
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new WorkUnit($jobId, $precursorId);
        $fragTol = 0.05;
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance(new Tolerance($fragTol, $fragUnit));
        
        $mods = array();
        $mods[21] = new Modification(21, 79.97, array(
            'C'
        ));
        $workUnit->addFixedModification($mods[21]);
        
        $fragments = array();
        $fragments[] = new FragmentIon(79.97, 150.5);
        $workUnit->addFragmentIon($fragments[0]);
        
        $peptides = array();
        $peptides[512] = new Peptide(512);
        $peptides[512]->setSequence('PEPTIDE');
        $peptides[512]->setScore(120.6, 5);
        
        $peptides[213] = new Peptide(213);
        $peptides[213]->setSequence('PEPTIDER');
        $peptides[213]->setScore(23.6, 9);
        
        $peptides[0] = new Peptide(0);
        $peptides[0]->setSequence('PEPTIDEK');
        
        $workUnit->addPeptide($peptides[512]);
        $workUnit->addPeptide($peptides[213]);
        $workUnit->addPeptide($peptides[0]);
        
        $this->assertEquals($workUnit, WorkUnit::fromJson($json));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJson
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJsonPeptides
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetValidWorkUnitFromJson2()
    {
        $json = '{"job":1,"precursor":1,"peptides":[{"id":44982,"score":2,"ionsMatched":2},{"id":1516198,"score":2,"ionsMatched":2},{"id":150121,"score":1,"ionsMatched":1},{"id":712838,"score":1,"ionsMatched":1},{"id":1534399,"score":1,"ionsMatched":1},{"id":1968911,"score":1,"ionsMatched":1},{"id":3177860,"score":1,"ionsMatched":1},{"id":3276166,"score":1,"ionsMatched":1},{"id":3373588,"score":1,"ionsMatched":1},{"id":3560751,"score":1,"ionsMatched":1}]}';
        $jobId = 1;
        $precursorId = 1;
        $workUnit = new WorkUnit($jobId, $precursorId);
        
        $peptide = new Peptide(44982);
        $peptide->setScore(2, 2);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(1516198);
        $peptide->setScore(2, 2);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(150121);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(712838);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(1534399);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(1968911);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(3177860);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(3276166);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(3373588);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $peptide = new Peptide(3560751);
        $peptide->setScore(1, 1);
        $workUnit->addPeptide($peptide);
        
        $this->assertEquals($workUnit, WorkUnit::fromJson($json));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJson
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetInvalidWorkUnitFromJson()
    {
        $json = '{"job":1,"precursor":1,"peptides":[{"id":44982,"score":2,"ionsMatched":2},{"id":1516198,"score":2,"ionsMatched":2},{"id":150121,"score":1,"ionsMatched":1},{"id":712838,"score":1,"ionsMatched":1},{"id":1534399,"score":1,"ionsMatched":1}{"id":1968911,"score":1,"ionsMatched":1},{"id":3177860,"score":1,"ionsMatched":1},{"id":3276166,"score":1,"ionsMatched":1},{"id":3373588,"score":1,"ionsMatched":1},{"id":3560751,"score":1,"ionsMatched":1}]}';
        WorkUnit::fromJson($json);
    }
}
