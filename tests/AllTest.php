<?php

use PHPUnit\Framework\TestCase;
use Mcnic\OtusPhp\SockServer;

class AllTest extends TestCase
{

    public function testAll()
    {
        $lib = new SockServer();
        $lib->work();

        $this->assertTrue(true);
    }

}
