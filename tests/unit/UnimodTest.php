<?php
require_once 'src/lib/UnimodImport.php';

class UnimodTest extends PHPUnit_Framework_TestCase
{
    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $unimod = new UnimodImport();
        $this->assertInstanceOf('UnimodImport', $unimod);

        return $unimod;
    }
}