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

use pgb_liv\crowdsource\BulkQuery;

class BulkQueryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers pgb_liv\crowdsource\BulkQuery::__construct
     *
     * @uses pgb_liv\crowdsource\BulkQuery
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        global $adodb;
        
        $this->cleanUp();
        
        $prefixQuery = 'INSERT INTO `workunit1` (`job`, precursor`) VALUES ';
        $bulkQuery = new BulkQuery($adodb, $prefixQuery);
        $this->assertInstanceOf('pgb_liv\crowdsource\BulkQuery', $bulkQuery);
        
        $this->cleanUp();
        
        return $bulkQuery;
    }

    /**
     * @covers pgb_liv\crowdsource\BulkQuery::__construct
     * @covers pgb_liv\crowdsource\BulkQuery::setBufferLength
     * @covers pgb_liv\crowdsource\BulkQuery::append
     * @covers pgb_liv\crowdsource\BulkQuery::execute
     * @covers pgb_liv\crowdsource\BulkQuery::close
     *
     * @uses pgb_liv\crowdsource\BulkQuery
     */
    public function testObjectCanAppendAndExecuteValidData()
    {
        global $adodb;
        
        $this->cleanUp();
        
        $prefixQuery = 'INSERT INTO `workunit1` (`job`, `precursor`) VALUES ';
        $bulkQuery = new BulkQuery($adodb, $prefixQuery);
        $bulkQuery->setBufferLength(1024);
        $this->assertInstanceOf('pgb_liv\crowdsource\BulkQuery', $bulkQuery);
        
        for ($i = 1; $i < 1000; $i ++) {
            $bulkQuery->append('(1, ' . $i . ')');
        }
        
        $bulkQuery->close();
        
        $rs = $adodb->Execute('SELECT `job`, `precursor` FROM `workunit1` ORDER BY `job` ASC, `precursor` ASC');
        
        $i = 1;
        foreach ($rs as $record) {
            $this->assertEquals(1, $record['job']);
            $this->assertEquals($i, $record['precursor']);
            
            $i ++;
        }
        
        $this->cleanUp();
    }

    /**
     * @covers pgb_liv\crowdsource\BulkQuery::__construct
     * @covers pgb_liv\crowdsource\BulkQuery::setBufferLength
     * @covers pgb_liv\crowdsource\BulkQuery::getBufferLength
     *
     * @uses pgb_liv\crowdsource\BulkQuery
     */
    public function testObjectCanGetSetBufferLength()
    {
        global $adodb;
        
        $this->cleanUp();
        
        $prefixQuery = 'INSERT INTO `workunit1` (`job`, `precursor`) VALUES ';
        $bulkQuery = new BulkQuery($adodb, $prefixQuery);
        $this->assertInstanceOf('pgb_liv\crowdsource\BulkQuery', $bulkQuery);
        
        $bulkQuery->setBufferLength(1024);
        $this->assertEquals(1024, $bulkQuery->getBufferLength());
        
        $this->cleanUp();
    }

    private function cleanUp()
    {
        global $adodb;
        
        $adodb->Execute('TRUNCATE `workunit1`');
    }
}