<?php
/*
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
namespace PGB_LIV\CrowdSource\Test\Unit;

use PGB_LIV\CrowdSource\Parser\MgfParser;

class MgfParserTest extends \PHPUnit_Framework_TestCase
{

    private function createTestFile(&$mgfEntries)
    {
        for ($entryIndex = 0; $entryIndex < 10; $entryIndex ++) {
            $entry = array();
            $entry['meta'] = array();
            $entry['meta']['TITLE'] = 'MY TEST RUN  (intensity=192543543.5801)';
            $entry['meta']['PEPMASS'] = rand(10000000, 1000000000) / 100000;
            ;
            $entry['meta']['CHARGE'] = '3+';
            $entry['meta']['SCANS'] = rand(1000, 10000);
            $entry['meta']['RTINSECONDS'] = rand(1000, 90000) / 100;
            $entry['ions'] = array();
            for ($ionIndex = 0; $ionIndex < 15; $ionIndex ++) {
                
                $entry['ions'][$ionIndex]['mz'] = rand(10000, 100000) / 100;
                $entry['ions'][$ionIndex]['intensity'] = rand(100000, 10000000) / 100;
            }
            
            $mgfEntries[] = $entry;
        }
        
        // Header
        $mgf = 'SEARCH=MIS';
        $mgf .= 'MASS=Monoisotopic' . "\n";
        
        foreach ($mgfEntries as $entry) {
            $mgf .= 'BEGIN IONS' . "\n";
            foreach ($entry['meta'] as $key => $value) {
                $mgf .= $key . '=' . $value . "\n";
            }
            
            foreach ($entry['ions'] as $ion) {
                $mgf .= $ion['mz'] . ' ' . $ion['intensity'] . "\n";
            }
            $mgf .= 'END IONS' . "\n";
        }
        
        $tempFile = tempnam(sys_get_temp_dir(), 'MgfParserTest');
        
        file_put_contents($tempFile, $mgf);
        
        return $tempFile;
    }

    /**
     * @covers MgfParser::__construct
     *
     * @uses MgfParser
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $mgfEntries = array();
        $mgfPath = $this->createTestFile($mgfEntries);
        $mgf = new MgfParser($mgfPath);
        $this->assertInstanceOf('PGB_LIV\CrowdSource\Parser\MgfParser', $mgf);
        
        return $mgf;
    }

    /**
     * @covers MgfParser::__construct
     *
     * @uses MgfParser
     */
    public function testCanRetrieveEntry()
    {
        $mgfEntries = array();
        $mgfPath = $this->createTestFile($mgfEntries);
        
        $mgf = new MgfParser($mgfPath);
        foreach ($mgf as $key => $entry) {
            $this->assertEquals($mgfEntries[$key - 1], $entry);
        }
    }
}
