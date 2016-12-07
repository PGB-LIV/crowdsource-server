<?php
require_once 'src/lib/FastaParser.php';

class FastaParserTest extends PHPUnit_Framework_TestCase
{

    private function createTestFile(&$fastaEntries)
    {
        $description = '>' . uniqid();
        $sequence = uniqid() . "\n";
        $sequence .= uniqid() . "\n";
        $sequence .= uniqid() . "\n";
        $sequence .= uniqid() . "\n";
        
        $fastaEntries[] = array(
            'description' => $description,
            'sequence' => $sequence
        );
        
        $fasta = $description . "\n" . $sequence;
        
        $tempFile = tempnam(sys_get_temp_dir(), 'FastaParserTest');
        
        file_put_contents($tempFile, $fasta);
        
        return $tempFile;
    }

    /**
     * @covers FastaParserTest::__construct
     * 
     * @uses FastaParserTest
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $fastaEntries = array();
        $fastaPath = $this->createTestFile(array());
        $fasta = new FastaParserTest($fastaPath);
        $this->assertInstanceOf('FastaParserTest', $fasta);
        
        return $fasta;
    }

    public function testCanRetrieveEntry()
    {
        $fastaEntries = array();
        $fastaPath = $this->createTestFile(array());
        
        $fastaPath = $this->createTestFile(array());
        $fasta = new FastaParserTest($fastaPath);
        foreach ($fasta as $key => $entry) {
            $this->assertEquals($fastaEntries[$key], $fasta);
        }
        
        return $fasta;
    }
}
