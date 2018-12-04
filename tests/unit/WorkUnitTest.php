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
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\crowdsource\Core\Modification;
use pgb_liv\php_ms\Core\Identification;

class WorkUnitTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $workUnit = new WorkUnit("one-two-three");
        $this->assertInstanceOf('pgb_liv\crowdsource\Core\WorkUnit', $workUnit);

        return $workUnit;
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getUid
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetConstructorArgs()
    {
        $uid = rand();
        $workUnit = new WorkUnit($uid);

        $this->assertEquals($uid, $workUnit->getUid());
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::addFixedModification
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFixedModifications
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidFixedModification()
    {
        $uid = rand();
        $workUnit = new WorkUnit($uid);

        $mods = array();
        $mods[25] = new Modification(25, 79.97, array(
            'C'
        ));
        $workUnit->addFixedModification($mods[25]);

        $this->assertEquals($mods, $workUnit->getFixedModifications());
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::addFragmentIon
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFragmentIons
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidFragmentIon()
    {
        $uid = rand();
        $workUnit = new WorkUnit($uid);

        $fragments = array();
        $fragments[0] = new FragmentIon(79.97, 150.5);
        $workUnit->addFragmentIon($fragments[0]);

        $this->assertEquals($fragments, $workUnit->getFragmentIons());
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::addIdentification
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getIdentifications
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidPeptide()
    {
        $uid = rand();
        $workUnit = new WorkUnit($uid);

        $identifications = array();
        $identifications[0] = new Identification();

        $peptide = new Peptide(0);
        $peptide->setSequence('PEPTIDE');

        $identifications[0]->setSequence($peptide);

        $workUnit->addIdentification($identifications[0]);

        $this->assertEquals($identifications, $workUnit->getIdentifications());
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::setFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFragmentTolerance
     * @covers pgb_liv\crowdsource\Core\WorkUnit::getFragmentToleranceUnit
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetSetValidFragmentTolerance1()
    {
        $uid = rand();
        $workUnit = new WorkUnit($uid);
        $fragTol = 10.0;
        $fragUnit = 'ppm';
        $workUnit->setFragmentTolerance(new Tolerance($fragTol, $fragUnit));

        $this->assertEquals($fragTol, $workUnit->getFragmentTolerance());
        $this->assertEquals($fragUnit, $workUnit->getFragmentToleranceUnit());
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::toJson
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetValidJsonFromWorkUnit()
    {
        $uid = "245-45235-6789";
        $json = '{"uid":"' . $uid .
            '","fragments":[{"mz":79.97,"intensity":150.5}],"peptides":[{"id":512,"sequence":"PEPTIDE","mods":[]},{"id":213,"sequence":"PEPTIDER","mods":[]},{"id":0,"sequence":"PEPTIDEK","mods":[]}],"fixedMods":[{"id":4,"mass":79.97,"residues":"C"}],"fragTol":0.05,"fragTolUnit":"Da"}';

        $workUnit = new WorkUnit($uid);
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

        $identifications = array();
        $identifications[512] = new Identification();
        $identifications[213] = new Identification();
        $identifications[0] = new Identification();

        $peptide = new Peptide(512);
        $peptide->setSequence('PEPTIDE');
        $identifications[512]->setSequence($peptide);

        $peptide = new Peptide(213);
        $peptide->setSequence('PEPTIDER');
        $identifications[213]->setSequence($peptide);

        $peptide = new Peptide(0);
        $peptide->setSequence('PEPTIDEK');
        $identifications[0]->setSequence($peptide);

        $workUnit->addIdentification($identifications[512]);
        $workUnit->addIdentification($identifications[213]);
        $workUnit->addIdentification($identifications[0]);

        $this->assertEquals($json, $workUnit->toJson());
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJson
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJsonPeptides
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetValidWorkUnitFromJson1()
    {
        $uid = "45-57342-42525";
        $json = '{"uid":"' . $uid .
            '","fragments":[{"mz":79.97,"intensity":150.5}],"peptides":[{"id":512,"sequence":"PEPTIDE","S":120.6,"IM":5},{"id":213,"sequence":"PEPTIDER","S":23.6,"IM":9},{"id":0,"sequence":"PEPTIDEK"}],"fixedMods":[{"id":21,"mass":79.97,"residues":"C"}],"fragTol":0.05,"fragTolUnit":"da"}';

        $workUnit = new WorkUnit($uid);
        $fragTol = 0.05;
        $fragUnit = 'da';
        $workUnit->setFragmentTolerance(new Tolerance($fragTol, $fragUnit));

        $fixedMod = new Modification(21, 79.97, array(
            'C'
        ));
        $workUnit->addFixedModification($fixedMod);

        $fragments = array();
        $fragments[] = new FragmentIon(79.97, 150.5);
        $workUnit->addFragmentIon($fragments[0]);

        $identifications = array();
        $identifications[512] = new Identification();
        $identifications[213] = new Identification();
        $identifications[0] = new Identification();

        $peptide = new Peptide(512);
        $peptide->setSequence('PEPTIDE');
        $identifications[512]->setSequence($peptide);
        $identifications[512]->setScore('-10lgP', 120.6);
        $identifications[512]->setIonsMatched(5);

        $peptide = new Peptide(213);
        $peptide->setSequence('PEPTIDER');
        $identifications[213]->setSequence($peptide);
        $identifications[213]->setScore('-10lgP', 23.6);
        $identifications[213]->setIonsMatched(9);

        $peptide = new Peptide(0);
        $peptide->setSequence('PEPTIDEK');
        $identifications[0]->setSequence($peptide);

        $workUnit->addIdentification($identifications[512]);
        $workUnit->addIdentification($identifications[213]);
        $workUnit->addIdentification($identifications[0]);

        $this->assertEquals($workUnit, WorkUnit::fromJson($json));
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::__construct
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJson
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJsonPeptides
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetValidWorkUnitFromJson2()
    {
        $uid = "1235-123-2215";
        $json = '{"uid":"' . $uid .
            '","peptides":[{"id":44982,"S":2,"IM":2},{"id":1516198,"S":2,"IM":2},{"id":150121,"S":1,"IM":1},{"id":712838,"S":1,"IM":1},{"id":1534399,"S":1,"IM":1},{"id":1968911,"S":1,"IM":1},{"id":3177860,"S":1,"IM":1},{"id":3276166,"S":1,"IM":1},{"id":3373588,"S":1,"IM":1},{"id":3560751,"S":1,"IM":1}]}';

        $workUnit = new WorkUnit($uid);

        $identification = new Identification();
        $identification->setSequence(new Peptide(44982));
        $identification->setScore('-10lgP', 2);
        $identification->setIonsMatched(2);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(1516198));
        $identification->setScore('-10lgP', 2);
        $identification->setIonsMatched(2);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(150121));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(712838));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(1534399));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(1968911));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(3177860));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(3276166));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(3373588));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $identification = new Identification();
        $identification->setSequence(new Peptide(3560751));
        $identification->setScore('-10lgP', 1);
        $identification->setIonsMatched(1);
        $workUnit->addIdentification($identification);

        $this->assertEquals($workUnit, WorkUnit::fromJson($json));
    }

    /**
     *
     * @covers pgb_liv\crowdsource\Core\WorkUnit::fromJson
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Core\WorkUnit
     */
    public function testObjectCanGetInvalidWorkUnitFromJson1()
    {
        $json = '{"uid":"one-two-three","peptides":[{"id":44982,"S":2,"IM":2},{"id":1516198,"S":2,"IM":2},{"id":150121,"S":1,"IM":1},{"id":712838,"S":1,"IM":1},{"id":1534399,"S":1,"IM":1}{"id":1968911,"S":1,"IM":1},{"id":3177860,"S":1,"IM":1},{"id":3276166,"S":1,"IM":1},{"id":3373588,"S":1,"IM":1},{"id":3560751,"S":1,"IM":1}]}';
        WorkUnit::fromJson($json);
    }
}
