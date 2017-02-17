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

use pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor;

class Phase2PreprocessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor::__construct
     *
     * @uses pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        global $adodb;
        
        $preprocessor = new Phase2Preprocessor($adodb, 1);
        $this->assertInstanceOf('pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor', $preprocessor);
        
        return $preprocessor;
    }

    /**
     * @covers pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor::__construct
     * @expectedException InvalidArgumentException
     *
     * @uses pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor
     */
    public function testObjectCanBeConstructedForInvalidConstructorArguments()
    {
        global $adodb;
        
        $preprocessor = new Phase2Preprocessor($adodb, 'fail');
        $this->assertInstanceOf('pgb_liv\crowdsource\Preprocessor\Phase2Preprocessor', $preprocessor);
    }

    private function cleanUp()
    {
        global $adodb;
        
        $adodb->Execute('TRUNCATE `fasta_peptides`');
        $adodb->Execute('TRUNCATE `raw_ms2`');
        $adodb->Execute('TRUNCATE `workunit1`');
        $adodb->Execute('TRUNCATE `workunit1_peptides`');
        $adodb->Execute('TRUNCATE `job_fixed_mod`');
        $adodb->Execute('TRUNCATE `job_queue`');
    }
}
