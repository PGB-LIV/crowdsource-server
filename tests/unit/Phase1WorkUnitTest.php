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
            'score' => null
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
            'score' => 120.6
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
            'score' => 120.6
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
}
