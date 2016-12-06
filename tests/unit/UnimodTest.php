<?php
use PHPUnit\Framework\TestCase;

class UnimodTest extends TestCase
{
    /**
     * @covers UnimodImport::__construct
     */
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $unimod = new UnimodImport();
        $this->assertInstanceOf('UnimodImport', $unimod);
		
        return $unimod;
    }
}