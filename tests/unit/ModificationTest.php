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

use pgb_liv\crowdsource\Core\Modification;

class ModificationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $modification = new Modification(21);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        return $modification;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments1()
    {
        $modification = new Modification('fail');
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::getId
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetConstructorArgs()
    {
        $id = 21;
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $this->assertEquals($id, $modification->getId());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setMonoisotopicMass
     * @covers pgb_liv\crowdsource\Core\Modification::getMonoisotopicMass
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetSetValidMass()
    {
        $id = 21;
        $monoMass = 321.4621;
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setMonoisotopicMass($monoMass);
        
        $this->assertEquals($monoMass, $modification->getMonoisotopicMass());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setMonoisotopicMass
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanSetInvalidMass()
    {
        $id = 21;
        $monoMass = 'fail';
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setMonoisotopicMass($monoMass);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setLocation
     * @covers pgb_liv\crowdsource\Core\Modification::getLocation
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetSetValidLocation()
    {
        $id = 21;
        $location = 6;
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setLocation($location);
        
        $this->assertEquals($location, $modification->getLocation());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setLocation
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanSetInvalidLocation()
    {
        $id = 21;
        $location = 'fail';
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setLocation($location);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setResidues
     * @covers pgb_liv\crowdsource\Core\Modification::getResidues
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetSetValidResidues()
    {
        $id = 21;
        $residues = array(
            'S',
            'T',
            'Y'
        );
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setResidues($residues);
        
        $this->assertEquals($residues, $modification->getResidues());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setResidues
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanSetInvalidResidues1()
    {
        $id = 21;
        $residues = array(
            'STY'
        );
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setResidues($residues);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setResidues
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanSetInvalidResidues2()
    {
        $id = 21;
        $residues = array(
            'ST',
            'Y'
        );
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setResidues($residues);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::setResidues
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanSetInvalidResidues3()
    {
        $id = 21;
        $residues = array();
        $modification = new Modification($id);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setResidues($residues);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::toArray
     * @covers pgb_liv\crowdsource\Core\Modification::fromArray
     * @covers pgb_liv\crowdsource\Core\Modification::getId
     * @covers pgb_liv\crowdsource\Core\Modification::getLocation
     * @covers pgb_liv\crowdsource\Core\Modification::getMonoisotopicMass
     * @covers pgb_liv\crowdsource\Core\Modification::getResidues
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanConvertToFromArray()
    {
        $modification = new Modification(21);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $modification->setMonoisotopicMass(63.256);
        $modification->setResidues(array(
            'S',
            'T',
            'Y'
        ));
        $modification->setLocation(12);
        
        $modificationArray = array();
        $modificationArray[Modification::ARRAY_ID] = $modification->getId();
        $modificationArray[Modification::ARRAY_LOCATION] = $modification->getLocation();
        $modificationArray[Modification::ARRAY_MASS] = $modification->getMonoisotopicMass();
        $modificationArray[Modification::ARRAY_RESIDUES] = implode('', $modification->getResidues());
        
        $this->assertEquals($modificationArray, $modification->toArray());
        $this->assertEquals($modification, Modification::fromArray($modificationArray));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::toArray
     * @covers pgb_liv\crowdsource\Core\Modification::fromArray
     * @covers pgb_liv\crowdsource\Core\Modification::getId
     * @covers pgb_liv\crowdsource\Core\Modification::getLocation
     * @covers pgb_liv\crowdsource\Core\Modification::getMonoisotopicMass
     * @covers pgb_liv\crowdsource\Core\Modification::getResidues
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanConvertFromArray1()
    {
        $modifications = array();
        $modification = new Modification(21);
        
        $modification->setMonoisotopicMass(63.256);
        $modification->setResidues(array(
            'S',
            'T',
            'Y'
        ));
        $modification->setLocation(12);
        $modifications[] = $modification;
        
        $modification = new Modification(21);
        $modification->setMonoisotopicMass(63.256);
        $modification->setResidues(array(
            'S',
            'T',
            'Y'
        ));
        $modification->setLocation(6);
        $modifications[] = $modification;
        
        $modificationArray = array();
        $modificationArray[Modification::ARRAY_ID] = $modification->getId();
        $modificationArray[Modification::ARRAY_LOCATION] = array(
            $modifications[0]->getLocation(),
            $modifications[1]->getLocation()
        );
        $modificationArray[Modification::ARRAY_MASS] = $modification->getMonoisotopicMass();
        $modificationArray[Modification::ARRAY_RESIDUES] = implode('', $modification->getResidues());
        
        $this->assertEquals($modifications, Modification::fromArray($modificationArray));
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::toArray
     * @covers pgb_liv\crowdsource\Core\Modification::fromArray
     * @covers pgb_liv\crowdsource\Core\Modification::getId
     * @covers pgb_liv\crowdsource\Core\Modification::getLocation
     * @covers pgb_liv\crowdsource\Core\Modification::getMonoisotopicMass
     * @covers pgb_liv\crowdsource\Core\Modification::getResidues
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanConvertFromArray2()
    {
        $modifications = array();
        $modification = new Modification(21);
        
        $modification->setMonoisotopicMass(63.256);
        $modification->setResidues(array(
            'S',
            'T',
            'Y'
        ));
        
        $modifications[] = $modification;
        
        $modification = new Modification(21);
        $modification->setMonoisotopicMass(63.256);
        $modification->setResidues(array(
            'S',
            'T',
            'Y'
        ));
        
        $modifications[] = $modification;
        
        $modificationArray = array();
        $modificationArray[Modification::ARRAY_ID] = $modification->getId();
        $modificationArray[Modification::ARRAY_OCCURRENCES] = 2;
        $modificationArray[Modification::ARRAY_MASS] = $modification->getMonoisotopicMass();
        $modificationArray[Modification::ARRAY_RESIDUES] = implode('', $modification->getResidues());
        
        $this->assertEquals($modifications, Modification::fromArray($modificationArray));
    }
}
