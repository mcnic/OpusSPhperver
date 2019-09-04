<?php

use Mcnic\OtusPhp\SockServer;
use PHPUnit\Framework\TestCase;

class AllTest extends TestCase
{

    public function testAll()
    {
        $lib = new SockServer();
        $lib->work();

        $this->assertTrue(true);
    }

}
