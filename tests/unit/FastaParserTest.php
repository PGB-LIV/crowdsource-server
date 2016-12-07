<?php
require_once 'src/lib/FastaParser.php';

class FastaParserTest extends PHPUnit_Framework_TestCase
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
     * @covers FastaParserTest::__construct
     *
     * @uses FastaParserTest
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $fastaEntries = array();
        $fastaPath = $this->createTestFile($fastaEntries);
        $fasta = new FastaParser($fastaPath);
        $this->assertInstanceOf('FastaParser', $fasta);
        
        return $fasta;
    }

    /**
     * @covers FastaParserTest::__construct
     * @covers FastaParserTest::next
     * @covers FastaParserTest::valid
     * @covers FastaParserTest::current
     * @covers FastaParserTest::rewind
     *
     * @uses FastaParserTest
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
