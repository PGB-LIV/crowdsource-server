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

use pgb_liv\crowdsource\Allocator\Phase1Allocator;

class Phase1AllocatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        global $adodb;
        
        $allocator = new Phase1Allocator($adodb, 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Allocator\Phase1Allocator', $allocator);
        
        return $allocator;
    }

    /**
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::__construct
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getWorkUnit
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getPeptides
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getFixedModifications
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getMs2
     * @covers pgb_liv\crowdsource\Allocator\Phase1Allocator::getPeptides
     *
     * @uses pgb_liv\crowdsource\Allocator\Phase1Allocator
     */
    public function testObjectCanGetWorkUnit()
    {
        global $adodb;
        
        $ms2 = $this->getMs2();
        $peptides = $this->getPeptides();
        $this->createWorkUnit(1, 1, $ms2, $peptides);
        
        $allocator = new Phase1Allocator($adodb, 1);
        $workUnit = $allocator->getWorkUnit();
        
        $this->assertEquals(1, $workUnit->job);
        $this->assertEquals(1, $workUnit->ms1);
        $this->assertEquals($ms2, $workUnit->ms2);
        $this->assertEquals($peptides, $workUnit->peptides);
        
        $this->cleanUp();
        
        return $allocator;
    }

    private function getPeptides()
    {
        $peptides = array();
        $peptides[0] = array(
            'id' => 0,
            'structure' => 'PEPTIDE'
        );
        $peptides[1] = array(
            'id' => 1,
            'structure' => 'PEPTIDER'
        );
        
        return $peptides;
    }

    private function getMs2()
    {
        $ms2 = array();
        
        $ms2[0] = array(
            'mz' => 100.5,
            'intensity' => 600.5
        );
        $ms2[1] = array(
            'mz' => 200.5,
            'intensity' => 500.5
        );
        $ms2[2] = array(
            'mz' => 300.5,
            'intensity' => 400.5
        );
        $ms2[3] = array(
            'mz' => 400.5,
            'intensity' => 300.5
        );
        $ms2[4] = array(
            'mz' => 500.5,
            'intensity' => 200.5
        );
        $ms2[5] = array(
            'mz' => 600.5,
            'intensity' => 100.5
        );
        
        return $ms2;
    }

    private function createWorkUnit($jobId, $ms1Id, $ms2, $peptides)
    {
        global $adodb;
        
        $adodb->Execute('INSERT INTO `workunit1` (`job`, `ms1`) VALUES (' . $jobId . ', ' . $ms1Id . ');');
        foreach ($ms2 as $key => $value) {
            $adodb->Execute(
                'INSERT INTO `raw_ms2` (`job`, `ms1`, `id`, `mz`, `intensity`) VALUES (' . $jobId . ', ' . $ms1Id . ', ' .
                     $key . ', ' . $value['mz'] . ', ' . $value['intensity'] . ');');
        }
        
        foreach ($peptides as $peptide) {
            $adodb->Execute(
                'INSERT INTO `workunit1_peptides` (`job`, `ms1`, `peptide`) VALUES (' . $jobId . ', ' . $ms1Id . ', ' .
                     $peptide['id'] . ');');
            $adodb->Execute(
                'INSERT INTO `fasta_peptides` (`job`, `id`, `peptide`) VALUES (' . $jobId . ', ' . $peptide['id'] . ', ' .
                     $adodb->quote($peptide['structure']) . ');');
        }
    }

    private function cleanUp()
    {
        global $adodb;
        
        $adodb->Execute('TRUNCATE `fasta_peptides`');
        $adodb->Execute('TRUNCATE `raw_ms2`');
        $adodb->Execute('TRUNCATE `workunit1`');
        $adodb->Execute('TRUNCATE `workunit1_peptides`');
    }
}
