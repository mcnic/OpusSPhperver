<?php


$pid = pcntl_fork();

if ($pid > 0) {
    echo "this is parent\n";
} elseif ($pid == 0) {
    echo "this is chld\n";
}
