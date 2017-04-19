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

class JsonModificationTest extends \PHPUnit_Framework_TestCase
{

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
