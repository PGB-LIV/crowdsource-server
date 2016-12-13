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
namespace PGB_LIV\CrowdSource\Test\Unit;

use PGB_LIV\CrowdSource\Parser\FastaParser;

class FastaParserTest extends \PHPUnit_Framework_TestCase
{

    private function createTestFile(&$fastaEntries)
    {
        $fasta = '';
        for ($i = 0; $i < 5; $i ++) {
            $description = '>' . uniqid();
            $sequence = uniqid() . "\n";
            $sequence .= uniqid() . "\n";
            $sequence .= uniqid() . "\n";
            $sequence .= uniqid() . "\n";
            
            $fastaEntries[] = array(
                'description' => substr($description, 1),
                'sequence' => str_replace("\n", '', $sequence)
            );
            
            $fasta .= $description . "\n" . $sequence . "\n";
        }
        
        $tempFile = tempnam(sys_get_temp_dir(), 'FastaParserTest');
        
        file_put_contents($tempFile, $fasta);
        
        return $tempFile;
    }

    /**
     * @covers PGB_LIV\CrowdSource\Parser\FastaParser::__construct
     *
     * @uses PGB_LIV\CrowdSource\Parser\FastaParser
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $fastaEntries = array();
        $fastaPath = $this->createTestFile($fastaEntries);
        $fasta = new FastaParser($fastaPath);
        $this->assertInstanceOf('\PGB_LIV\CrowdSource\Parser\FastaParser', $fasta);
        
        return $fasta;
    }

    /**
     * @covers PGB_LIV\CrowdSource\Parser\FastaParser::__construct
     *
     * @uses PGB_LIV\CrowdSource\Parser\FastaParser
     */
    public function testCanRetrieveEntry()
    {
        $fastaEntries = array();
        $fastaPath = $this->createTestFile($fastaEntries);
        
        $fasta = new FastaParser($fastaPath);
        foreach ($fasta as $key => $entry) {
            $this->assertEquals($fastaEntries[$key - 1], $entry);
        }
    }
}
