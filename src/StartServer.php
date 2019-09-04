<?php
require_once __DIR__ . "\\..\\vendor\\autoload.php";

use Mcnic\OtusPhp\SockServer;

$lib = new SockServer();
$lib->work();
