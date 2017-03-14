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

use pgb_liv\crowdsource\Core\Peptide;
use pgb_liv\crowdsource\Core\Modification;

class PeptideTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $peptide = new Peptide(10);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        return $peptide;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments1()
    {
        $peptide = new Peptide('fail');
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::getId
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanGetConstructorArgs()
    {
        $id = 15;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $this->assertEquals($id, $peptide->getId());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::setSequence
     * @covers pgb_liv\crowdsource\Core\Peptide::getSequence
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanGetSetValidSequence()
    {
        $id = 15;
        $sequence = 'PEPTIDE';
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $peptide->setSequence($sequence);
        
        $this->assertEquals($sequence, $peptide->getSequence());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::setScore
     * @covers pgb_liv\crowdsource\Core\Peptide::getScore
     * @covers pgb_liv\crowdsource\Core\Peptide::getIonsMatched
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanGetSetValidScore()
    {
        $id = 15;
        $score = 133.7;
        $ions = 12;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $peptide->setScore($score, $ions);
        
        $this->assertEquals($score, $peptide->getScore());
        $this->assertEquals($ions, $peptide->getIonsMatched());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::setScore
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanGetSetInvalidScore1()
    {
        $id = 15;
        $score = 'fail';
        $ions = 12;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $peptide->setScore($score, $ions);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::setScore
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanGetSetInvalidScore2()
    {
        $id = 15;
        $score = 151.456;
        $ions = 'fail';
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $peptide->setScore($score, $ions);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::addModification
     * @covers pgb_liv\crowdsource\Core\Peptide::getModifications
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetSetValidModification()
    {
        $id = 15;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $mods = array();
        $mods[] = new Modification(4, 146.14, array(
            'M'
        ));
        $peptide->addModification($mods[0]);
        
        $this->assertEquals($mods, $peptide->getModifications());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::addModification
     * @covers pgb_liv\crowdsource\Core\Peptide::addModifications
     * @covers pgb_liv\crowdsource\Core\Peptide::getModifications
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetSetValidModifications()
    {
        $id = 15;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $mods = array();
        $mods[0] = new Modification(4, 146.14, array(
            'M'
        ));
        $mods[1] = new Modification(4, 146.14, array(
            'M'
        ));
        $peptide->addModifications($mods);
        
        $this->assertEquals($mods, $peptide->getModifications());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::IsModified
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanGetIsModified1()
    {
        $id = 15;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $this->assertEquals(false, $peptide->isModified());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::IsModified
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetIsModified2()
    {
        $id = 15;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $mods = array();
        $mods[4] = new Modification(4, 146.14, array(
            'M'
        ));
        $peptide->addModification($mods[4]);
        
        $this->assertEquals(true, $peptide->isModified());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::__construct
     * @covers pgb_liv\crowdsource\Core\Peptide::setSequence
     * @covers pgb_liv\crowdsource\Core\Peptide::setScore
     * @covers pgb_liv\crowdsource\Core\Peptide::addModification
     * @covers pgb_liv\crowdsource\Core\Peptide::toArray
     * @covers pgb_liv\crowdsource\Core\Peptide::toArrayMods
     * @covers pgb_liv\crowdsource\Core\Peptide::fromArray
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanConvertToFromValidArray()
    {
        $peptideArray = array();
        $id = 15;
        $sequence = 'PEPTIDE';
        $score = 156.1;
        $ionsMatched = 5;
        $mod = new Modification(4, 146.14, array(
            'M'
        ));
        
        $peptide = new Peptide($id);
        $peptideArray[Peptide::ARRAY_ID] = $id;
        
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        $peptide->setSequence($sequence);
        $peptideArray[Peptide::ARRAY_SEQUENCE] = $sequence;
        
        $this->assertEquals($peptideArray, $peptide->toArray());
        
        $peptide->setScore($score, $ionsMatched);
        $peptideArray[Peptide::ARRAY_SCORE] = $score;
        $peptideArray[Peptide::ARRAY_IONS] = $ionsMatched;
        
        $peptide->addModification($mod);
        $peptideArray[Peptide::ARRAY_MODIFICATIONS] = array();
        $peptideArray[Peptide::ARRAY_MODIFICATIONS][] = $mod->toArray();
        $peptideArray[Peptide::ARRAY_MODIFICATIONS][0][Modification::ARRAY_OCCURRENCES] = 1;
        
        $this->assertEquals($peptideArray, $peptide->toArray());
        $this->assertEquals($peptide, Peptide::fromArray($peptideArray));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Peptide::fromArray
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     */
    public function testObjectCanConvertFromInvalidArray()
    {
        $peptideArray = array();
        $id = 'fail';
        
        $peptideArray[Peptide::ARRAY_ID] = $id;
        
        Peptide::fromArray($peptideArray);
    }
}
