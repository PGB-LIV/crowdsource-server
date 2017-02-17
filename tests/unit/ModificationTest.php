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
        $modification = new Modification(21, 79.97, array(
            'S',
            'T',
            'Y'
        ));
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
        $modification = new Modification('fail', 79.97, 
            array(
                'S',
                'T',
                'Y'
            ));
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments2()
    {
        $modification = new Modification(21, 'fail', array(
            'STY'
        ));
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments3()
    {
        $modification = new Modification(21, 79.97, array(
            '1234'
        ));
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\Modification::__construct
     * @covers pgb_liv\crowdsource\Core\Modification::getId
     * @covers pgb_liv\crowdsource\Core\Modification::getMonoisotopicMass
     * @covers pgb_liv\crowdsource\Core\Modification::getResidues
     *
     * @uses pgb_liv\crowdsource\Core\Modification
     */
    public function testObjectCanGetConstructorArgs()
    {
        $id = 21;
        $mass = 79.97;
        $residues = array(
            'S',
            'T',
            'Y'
        );
        $modification = new Modification($id, $mass, $residues);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\Modification', $modification);
        
        $this->assertEquals($id, $modification->getId());
        $this->assertEquals($mass, $modification->getMonoisotopicMass());
        $this->assertEquals($residues, $modification->getResidues());
    }
}
