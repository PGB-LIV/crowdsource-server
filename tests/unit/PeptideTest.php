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
     * @covers pgb_liv\crowdsource\Core\Peptide::setModification
     * @covers pgb_liv\crowdsource\Core\Peptide::getModifications
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     *
     * @uses pgb_liv\crowdsource\Core\Peptide
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetSetValid()
    {
        $id = 15;
        $peptide = new Peptide($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Peptide', $peptide);
        
        $mods = array();
        $mods[4] = new Modification(4, 146.14, array(
            'M'
        ));
        $peptide->addModification($mods[4]);
        
        $this->assertEquals($mods, $peptide->getModifications());
    }
}
