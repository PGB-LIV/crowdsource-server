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

class Phase1WorkUnitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $workUnit = new Phase1WorkUnit(1, 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Phase1WorkUnit', $workUnit);
        
        return $workUnit;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments1()
    {
        $workUnit = new Phase1WorkUnit('fail', 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Phase1WorkUnit', $workUnit);
        
        return $workUnit;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments2()
    {
        $workUnit = new Phase1WorkUnit(1, 'fail');
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Phase1WorkUnit', $workUnit);
        
        return $workUnit;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getJobId
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getPrecursorId
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetConstructorArgs()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $this->assertEquals($jobId, $workUnit->getJobId());
        $this->assertEquals($precursorId, $workUnit->getPrecursorId());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addFixedModification
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getFixedModifications
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidFixedModification()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $mods = array();
        $mods[] = array(
            'mass' => 79.97,
            'residue' => 'C'
        );
        $workUnit->addFixedModification($mods[0]['mass'], $mods[0]['residue']);
        
        $this->assertEquals($mods, $workUnit->getFixedModifications());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addFixedModification
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidFixedModification1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $mods = array();
        $mods[] = array(
            'mass' => 'fail',
            'residue' => 'C'
        );
        $workUnit->addFixedModification($mods[0]['mass'], $mods[0]['residue']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addFixedModification
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidFixedModification2()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $mods = array();
        $mods[] = array(
            'mass' => 79.97,
            'residue' => 'fail'
        );
        $workUnit->addFixedModification($mods[0]['mass'], $mods[0]['residue']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addFragmentIon
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getFragmentIons
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidFragmentIon()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $fragments = array();
        $fragments[] = array(
            'mz' => 79.97,
            'intensity' => 150.5
        );
        $workUnit->addFragmentIon($fragments[0]['mz'], $fragments[0]['intensity']);
        
        $this->assertEquals($fragments, $workUnit->getFragmentIons());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addFragmentIon
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidFragmentIon1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $fragments = array();
        $fragments[] = array(
            'mz' => 'fail',
            'intensity' => 150.5
        );
        $workUnit->addFragmentIon($fragments[0]['mz'], $fragments[0]['intensity']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addFragmentIon
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidFragmentIon2()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $fragments = array();
        $fragments[] = array(
            'mz' => 79.97,
            'intensity' => 'fail'
        );
        $workUnit->addFragmentIon($fragments[0]['mz'], $fragments[0]['intensity']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptide
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getPeptides
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidPeptide()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = array(
            'sequence' => 'PEPTIDE',
            'score' => null,
            'ionsMatched' => null
        );
        $workUnit->addPeptide(0, $peptides[0]['sequence']);
        
        $this->assertEquals($peptides, $workUnit->getPeptides());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptide
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidPeptide()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = array(
            'id' => 'fail',
            'sequence' => 'PEPTIDE',
            'score' => null
        );
        $workUnit->addPeptide($peptides[0]['id'], $peptides[0]['sequence']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptide
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptideScore
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getPeptides
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidPeptideScore1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = array(
            'sequence' => 'PEPTIDE',
            'score' => 120.6,
            'ionsMatched' => null
        );
        $workUnit->addPeptide(0, $peptides[0]['sequence']);
        $workUnit->addPeptideScore(0, $peptides[0]['score']);
        
        $this->assertEquals($peptides, $workUnit->getPeptides());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptideScore
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getPeptides
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidPeptideScore2()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = array(
            'sequence' => null,
            'score' => 120.6,
            'ionsMatched' => null
        );
        $workUnit->addPeptideScore(0, $peptides[0]['score']);
        
        $this->assertEquals($peptides, $workUnit->getPeptides());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptideScore
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidPeptideScore1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = array(
            'id' => 'fail',
            'sequence' => 'PEPTIDE',
            'score' => 13.37
        );
        $workUnit->addPeptideScore($peptides[0]['id'], $peptides[0]['score']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::addPeptideScore
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetInvalidPeptideScore2()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $peptides = array();
        $peptides[] = array(
            'id' => 0,
            'sequence' => 'PEPTIDE',
            'score' => 'fail'
        );
        $workUnit->addPeptideScore($peptides[0]['id'], $peptides[0]['score']);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::setFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getFragmentToleranceUnit
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidFragmentTolerance1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $fragTol = 10.0;
        $fragUnit = 'ppm';
        $workUnit->setFragmentTolerance($fragTol, $fragUnit);
        
        $this->assertEquals($fragTol, $workUnit->getFragmentTolerance());
        $this->assertEquals($fragUnit, $workUnit->getFragmentToleranceUnit());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::setFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::getFragmentToleranceUnit
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetSetValidFragmentTolerance2()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $fragTol = 0.05;
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance($fragTol, $fragUnit);
        
        $this->assertEquals($fragTol, $workUnit->getFragmentTolerance());
        $this->assertEquals($fragUnit, $workUnit->getFragmentToleranceUnit());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::setFragmentTolerance
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanSetInvalidFragmentTolerance1()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $fragTol = 'fail';
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance($fragTol, $fragUnit);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::setFragmentTolerance
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanSetInvalidFragmentTolerance2()
    {
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $fragTol = 0.05;
        $fragUnit = 'dalton';
        $workUnit->setFragmentTolerance($fragTol, $fragUnit);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::toJson
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetValidJsonFromWorkUnit()
    {
        $json = '{"job":1,"precursor":2,"fragments":[{"mz":79.97,"intensity":150.5}],"peptides":[{"id":512,"sequence":"PEPTIDE"},{"id":213,"sequence":"PEPTIDER"},{"id":0,"sequence":"PEPTIDEK"}],"fixedMods":[{"mass":79.97,"residue":"C"}],"fragTol":0.05,"fragTolUnit":"da"}';
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $fragTol = 0.05;
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance($fragTol, $fragUnit);
        
        $mods = array();
        $mods[] = array(
            'mass' => 79.97,
            'residue' => 'C'
        );
        $workUnit->addFixedModification($mods[0]['mass'], $mods[0]['residue']);
        
        $fragments = array();
        $fragments[] = array(
            'mz' => 79.97,
            'intensity' => 150.5
        );
        $workUnit->addFragmentIon($fragments[0]['mz'], $fragments[0]['intensity']);
        
        $peptides = array();
        $peptides[512] = array(
            'sequence' => 'PEPTIDE',
            'score' => null
        );
        $peptides[213] = array(
            'sequence' => 'PEPTIDER',
            'score' => null
        );
        $peptides[0] = array(
            'sequence' => 'PEPTIDEK',
            'score' => null
        );
        $workUnit->addPeptide(512, $peptides[512]['sequence']);
        $workUnit->addPeptide(213, $peptides[213]['sequence']);
        $workUnit->addPeptide(0, $peptides[0]['sequence']);
        
        $this->assertEquals($json, $workUnit->toJson());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::fromJson
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetValidWorkUnitFromJson1()
    {
        $json = '{"job":1,"precursor":2,"fragments":[{"mz":79.97,"intensity":150.5}],"peptides":[{"id":512,"sequence":"PEPTIDE","score":120.6},{"id":213,"sequence":"PEPTIDER","score":23.6},{"id":0,"sequence":"PEPTIDEK"}],"fixedMods":[{"mass":79.97,"residue":"C"}],"fragTol":0.05,"fragTolUnit":"da"}';
        $jobId = 1;
        $precursorId = 2;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        $fragTol = 0.05;
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance($fragTol, $fragUnit);
        
        $mods = array();
        $mods[] = array(
            'mass' => 79.97,
            'residue' => 'C'
        );
        $workUnit->addFixedModification($mods[0]['mass'], $mods[0]['residue']);
        
        $fragments = array();
        $fragments[] = array(
            'mz' => 79.97,
            'intensity' => 150.5
        );
        $workUnit->addFragmentIon($fragments[0]['mz'], $fragments[0]['intensity']);
        
        $peptides = array();
        $peptides[512] = array(
            'sequence' => 'PEPTIDE',
            'score' => 120.6
        );
        $peptides[213] = array(
            'sequence' => 'PEPTIDER',
            'score' => 23.6
        );
        $peptides[0] = array(
            'sequence' => 'PEPTIDEK',
            'score' => null
        );
        $workUnit->addPeptide(512, $peptides[512]['sequence']);
        $workUnit->addPeptide(213, $peptides[213]['sequence']);
        $workUnit->addPeptide(0, $peptides[0]['sequence']);
        $workUnit->addPeptideScore(512, $peptides[512]['score']);
        $workUnit->addPeptideScore(213, $peptides[213]['score']);
        
        $this->assertEquals($workUnit, Phase1WorkUnit::fromJson($json));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::fromJson
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetValidWorkUnitFromJson2()
    {
        $json = '{"job":1,"precursor":1,"peptides":[{"id":44982,"score":2,"ionsMatched":2},{"id":1516198,"score":2,"ionsMatched":2},{"id":150121,"score":1,"ionsMatched":1},{"id":712838,"score":1,"ionsMatched":1},{"id":1534399,"score":1,"ionsMatched":1},{"id":1968911,"score":1,"ionsMatched":1},{"id":3177860,"score":1,"ionsMatched":1},{"id":3276166,"score":1,"ionsMatched":1},{"id":3373588,"score":1,"ionsMatched":1},{"id":3560751,"score":1,"ionsMatched":1}]}';
        $jobId = 1;
        $precursorId = 1;
        $workUnit = new Phase1WorkUnit($jobId, $precursorId);
        
        $workUnit->addPeptideScore(44982, 2, 2);
        $workUnit->addPeptideScore(1516198, 2, 2);
        
        $workUnit->addPeptideScore(150121, 1, 1);
        $workUnit->addPeptideScore(712838, 1, 1);
        
        $workUnit->addPeptideScore(1534399, 1, 1);
        $workUnit->addPeptideScore(1968911, 1, 1);
        
        $workUnit->addPeptideScore(3177860, 1, 1);
        $workUnit->addPeptideScore(3276166, 1, 1);
        
        $workUnit->addPeptideScore(3373588, 1, 1);
        $workUnit->addPeptideScore(3560751, 1, 1);
        
        $this->assertEquals($workUnit, Phase1WorkUnit::fromJson($json));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\Phase1WorkUnit::fromJson
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Phase1WorkUnit
     */
    public function testObjectCanGetInvalidWorkUnitFromJson()
    {
        $json = '{"job":1,"precursor":1,"peptides":[{"id":44982,"score":2,"ionsMatched":2},{"id":1516198,"score":2,"ionsMatched":2},{"id":150121,"score":1,"ionsMatched":1},{"id":712838,"score":1,"ionsMatched":1},{"id":1534399,"score":1,"ionsMatched":1}{"id":1968911,"score":1,"ionsMatched":1},{"id":3177860,"score":1,"ionsMatched":1},{"id":3276166,"score":1,"ionsMatched":1},{"id":3373588,"score":1,"ionsMatched":1},{"id":3560751,"score":1,"ionsMatched":1}]}';
        Phase1WorkUnit::fromJson($json);
    }
}
