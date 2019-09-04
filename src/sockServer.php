#!/usr/local/bin/php -q
<?php

namespace Mcnic\OtusPhp;

require_once __DIR__ . "\\..\\vendor\\autoload.php";

use Mcnic\OtusPhp\CheckBracket;

//error_reporting(E_ALL);

set_time_limit(0);

class MyServer
{
    private $address = 'localhost';
    private $port = 10000;
    private $maxConnects = 5;

    public function workMulti()
    {
        $this->setPort();
        echo "server worked on '" . $this->address . ":" . $this->port . "'\n";

        /* Turn on implicit output flushing so we see what we're getting
         * as it comes in. */
        ob_implicit_flush();

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        }

        // set the option to reuse the port
        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

        if (socket_bind($sock, $this->address, $this->port) === false) {
            echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        if (socket_listen($sock, $this->maxConnects) === false) {
            echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        // create a list of all the clients that will be connected to us..
        // add the listening socket to this list
        $clients = array($sock);

        try {
            $lib = new CheckBracket();
        } catch (\Throwable $th) {
            echo $th;
            socket_close($sock);
            exit;
        }

        while (true) {
            // create a copy, so $clients doesn't get modified by socket_select()
            $read = $clients;
            $write = $except = null;
            $tv_sec = null;

            //echo "'read' before=" . print_r($read, 1) . "\n";

            // get a list of all the clients that have data to be read from
            // if there are no clients with data, go to next iteration
            if (socket_select($read, $write, $except, $tv_sec) < 1) {
                continue;
            }

            //echo "'read' after socket_select=" . print_r($read, 1) . "\n";
            //echo "'sock'=" . print_r($sock, 1) . "\n";

            // check if there is a client trying to connect
            if (in_array($sock, $read)) {

                // accept the client, and add him to the $clients array
                $clients[] = $newsock = socket_accept($sock);
                //echo "'new sock'=" . print_r($sock, 1) . "\n";
                //echo "'newsock'=" . print_r($newsock, 1) . "\n";
                //echo "'clients'=" . print_r($clients, 1) . "\n";

                // send the client a welcome message
                socket_write($newsock, "There are " . (count($clients) - 1) . " client(s) connected to the server\n");

                socket_getpeername($newsock, $ip);
                echo "New client connected: {$ip}\n";

                // remove the listening socket from the clients-with-data array
                $key = array_search($sock, $read);
                unset($read[$key]);
            }

            /*
            if (($msgsock = socket_accept($sock)) === false) {
            echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
            break;
            }

            $msg = "\nWelcome to the PHP Test Server. \n" .
            "To quit, type 'quit'\n"; //. To shut down the server type 'shutdown'.\n";
            socket_write($msgsock, $msg, strlen($msg));
             */
            //echo "final read=" . print_r($read, 1) . "\n";

            foreach ($read as $read_sock) {
                $buf = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

                //echo "socket_read[" . (string) $read_sock . "]=" . print_r($buf, 1) . "\n";

                // check if the client is disconnected
                if ($buf === false) {
                    // remove client for $clients array
                    $key = array_search($read_sock, $clients);
                    unset($clients[$key]);
                    //echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . " or ";
                    echo "client disconnected.\n";
                    // continue to the next client to read from, if any
                    continue;
                }

                $buf = trim($buf);

                // check if there is any data after trimming off the spaces
                if (!empty($buf)) {

                    // send this to all the clients in the $clients array (except the first one, which is a listening socket)
                    foreach ($clients as $send_sock) {

                        //echo print_r($sock) . "\n";
                        // if its the listening sock or the client that we got the message from, go to the next one in the list
                        //if ($send_sock == $sock || $send_sock == $read_sock) {
                        if ($send_sock == $sock || $send_sock !== $read_sock) {
                            //echo "it's initial sock, continue\n";
                            continue;
                        }

                        if ($buf == 'quit') {
                            break 1;
                        }

                        $talkback = $this->testBracket($lib, $buf) . "\n";
                        socket_write($send_sock, $talkback, strlen($talkback));
                        echo (string) $read_sock . ": test '$buf' = $talkback\n";
                    } // end of broadcast foreach

                }

                /*if ($buf == 'shutdown') {
                socket_close($msgsock);
                break 2;
                }*/
                //$talkback = "PHP: You said '$buf'.\n";
            };
        };
        socket_close($sock);
    }

    public function work()
    {
        $options = $this->getOpt();

        if (array_key_exists('h', $options) or array_key_exists('help', $options)) {
            echo $this->getHelp();
            exit;
        }

        if (array_key_exists('p', $options)) {
            $this->port = (int) $options['p'];
        }

        if (array_key_exists('port', $options)) {
            $this->port = (int) $options['port'];
        }

        echo "server worked on '" . $this->address . ":" . $this->port . "'\n";

        /* Turn on implicit output flushing so we see what we're getting
         * as it comes in. */
        ob_implicit_flush();

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        }

        if (socket_bind($sock, $this->address, $this->port) === false) {
            echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        if (socket_listen($sock, 5) === false) {
            echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        }

        try {
            $lib = new CheckBracket();
        } catch (\Throwable $th) {
            echo $th;
            exit;
        }

        do {
            if (($msgsock = socket_accept($sock)) === false) {
                echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
                break;
            }
            /* Send instructions. */
            $msg = "\nWelcome to the PHP Test Server. \n" .
                "To quit, type 'quit'\n"; //. To shut down the server type 'shutdown'.\n";
            socket_write($msgsock, $msg, strlen($msg));

            do {
                if (false === ($buf = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
                    echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
                    break 2;
                }
                if (!$buf = trim($buf)) {
                    continue;
                }
                if ($buf == 'quit') {
                    break 1;
                }
                /*if ($buf == 'shutdown') {
                socket_close($msgsock);
                break 2;
                }*/
                //$talkback = "PHP: You said '$buf'.\n";

                try {
                    $res = $lib->check($buf);
                    var_dump($res);
                    if ($res == true) {
                        $talkback = "correct\n";
                        socket_write($msgsock, $talkback, strlen($talkback));
                        echo "'$buf': Correct\n";
                    } else {
                        $talkback = "incorrect\n";
                        socket_write($msgsock, $talkback, strlen($talkback));
                        echo "'$buf': Inorrect\n";
                    }
                } catch (\InvalidArgumentException $th) {
                    $talkback = "Incorrect symbols. Allow only '(', ')', '\\n', '\\t', '\\r'\n";
                    socket_write($msgsock, $talkback, strlen($talkback));
                    echo "Incorrect symbols in '$buf'\n";
                } catch (\Throwable $th) {
                    $talkback = $th;
                    socket_write($msgsock, $talkback, strlen($talkback));
                    echo "error: $talkback\n";
                }
            } while (true);
            socket_close($msgsock);
        } while (true);

        socket_close($sock);
    }

    private function setPort()
    {
        $options = $this->getOpt();

        if (array_key_exists('h', $options) or array_key_exists('help', $options)) {
            echo $this->getHelp();
            exit;
        }

        if (array_key_exists('p', $options)) {
            $this->port = (int) $options['p'];
        }

        if (array_key_exists('port', $options)) {
            $this->port = (int) $options['port'];
        }
    }

    private function getOpt()
    {
        $shortopts = "";
        //$shortopts .= "f:";  // Обязательное значение
        $shortopts .= "p:"; // Необязательное значение
        $shortopts .= "h"; // Необязательное значение
        //$shortopts .= "abc"; // Эти параметры не принимают никаких значений

        $longopts = array(
            //    "required:",     // Обязательное значение
            "port:", // Обязательное значение
            "help", // Нет значения
            //    "option",        // Нет значения
            //    "opt",           // Нет значения
        );
        $options = getopt($shortopts, $longopts);
        //var_dump($options);

        return $options;
    }

    public function getHelp()
    {
        return "paramaters:
            '-h | --help' - this help text
            '-p | --port' - set port to listen\n
            to connect servet type 'telnet <address> <port>'
            default adress='" . $this->address . "'
            default port=" . $this->port;
    }

    private function testBracket($lib, $buf)
    {
        try {
            $res = $lib->check($buf);
            //var_dump($res);
            if ($res == true) {
                return "correct";
            } else {
                return "incorrect";
            }
        } catch (\InvalidArgumentException $th) {
            return "Incorrect symbols. Allow only '(', ')', '\\n', '\\t', '\\r'";
        } catch (\Throwable $th) {
            return $th;
        }
    }
}

$server = new MyServer();
$server->workMulti();