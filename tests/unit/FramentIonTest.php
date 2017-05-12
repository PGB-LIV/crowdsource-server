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

use pgb_liv\crowdsource\Core\FragmentIon;

class FragmentIonTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Core\FragmentIon::__construct
     *
     * @uses pgb_liv\crowdsource\Core\FragmentIon
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $fragment = new FragmentIon(25.7, 2521.843);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\FragmentIon', $fragment);
        
        return $fragment;
    }

    /**
     * @covers pgb_liv\crowdsource\Core\FragmentIon::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\FragmentIon
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments1()
    {
        $fragment = new FragmentIon('fail', 2521.843);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\FragmentIon', $fragment);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\FragmentIon::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\FragmentIon
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments2()
    {
        $fragment = new FragmentIon(25.7, 'fail');
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\FragmentIon', $fragment);
    }

    /**
     * @covers pgb_liv\crowdsource\Core\FragmentIon::__construct
     * @covers pgb_liv\crowdsource\Core\FragmentIon::getMassCharge
     * @covers pgb_liv\crowdsource\Core\FragmentIon::getIntensity
     *
     * @uses pgb_liv\crowdsource\Core\FragmentIon
     */
    public function testObjectCanGetConstructorArgs()
    {
        $mz = 2341.3632;
        $intensity = 1344721.2572;
        $fragment = new FragmentIon($mz, $intensity);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\FragmentIon', $fragment);
        
        $this->assertEquals($mz, $fragment->getMassCharge());
        $this->assertEquals($intensity, $fragment->getIntensity());
    }

    /**
     * @covers pgb_liv\crowdsource\Core\FragmentIon::__construct
     * @covers pgb_liv\crowdsource\Core\FragmentIon::toArray
     * @covers pgb_liv\crowdsource\Core\FragmentIon::fromArray
     * @covers pgb_liv\crowdsource\Core\FragmentIon::getMassCharge
     * @covers pgb_liv\crowdsource\Core\FragmentIon::getIntensity
     *
     * @uses pgb_liv\crowdsource\Core\FragmentIon
     */
    public function testObjectCanConvertToFromArray()
    {
        $mz = 2341.3632;
        $intensity = 1344721.2572;
        $fragment = new FragmentIon($mz, $intensity);
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\FragmentIon', $fragment);
        
        $fragmentArray = array();
        $fragmentArray[FragmentIon::ARRAY_MZ] = $fragment->getMassCharge();
        $fragmentArray[FragmentIon::ARRAY_INTENSITY] = $fragment->getIntensity();
        
        $this->assertEquals($fragmentArray, $fragment->toArray());
        $this->assertEquals($fragment, FragmentIon::fromArray($fragmentArray));
    }
}
