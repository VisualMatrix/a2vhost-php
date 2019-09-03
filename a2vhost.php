#!/usr/bin/env php
<?php

class vhost {
    var $ssl;
    var $root;
    var $domain;
    function __construct($ssl, $root, $domain) {
        $this->ssl = $ssl;
        $this->root = $root;
        $this->domain = $domain;
    }
    private function makeRoot() {
        echo $this->root.PHP_EOL;
        if (!file_exists($this->root)) {
            mkdir($this->root, 0777, true);
        }
    }
}
 
if (requiredInput($argv)) {
    if (in_array("--help", $argv) || in_array("-h", $argv)) {
        help();
    } else {
        $opt = getOption($argv);
        $vhost = new vhost($opt["ssl"], $opt["root"], $opt["domain"]);
        run();
    }
} else {
    help();
}

/* assets function start heer */
function help() {
    showBanner();
    showUsage();
    exit(0);
}

function requiredInput($inputs) {

    $required = ["--domain=", "--root="];
    $pass = 0; $validSSL = false; $validDomain = false;
    foreach($inputs as $input) {
        if (startsWith($input, "--root=")) {
            $pass += 1;
        } else if (startsWith($input, "--domain=")) {
            $pass += 1;
            $raw = explode("=", $input);
            if (validateDomain($raw[1])) {
                $validDomain = true;
            }
        } else if (startsWith($input, "--ssl=")) {
            $raw = explode("=", $input);
            if (in_array($raw[1], ["true", "false", "True", "False"])) {
                $validSSL = true;
            }
        }
    }
    if($pass == count($required) && $validSSL && $validDomain) {
        return true;
    } else {
        return false;
    }
}

function getOption($arr) {
    foreach($arr as $index => $option) {
        if($index == 0) {
            continue;
        }
        $raw = explode("=", $option);
        if ($raw[0] == "--ssl") {
            $_ssl = $raw[1];
        } else {
            $_ssl = "false";
        }
        if ($raw[0] == "--root") {
            $_root = $raw[1];
        }
        if ($raw[0] == "--domain") {
            $_domain = $raw[1];
        }
    }
    return array('ssl' => $_ssl, 'root' => $_root, 'domain' => $_domain);
}

function validateDomain($domain) {
    return preg_match("/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/", $domain);
}


function startsWith ($string, $startString) { 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
} 

function showBanner() {
    echo '
            _____      ___    _           _   
      /\   |__ \ \    / / |  | |         | |  
     /  \     ) \ \  / /| |__| | ___  ___| |_ 
    / /\ \   / / \ \/ / |  __  |/ _ \/ __| __|
   / ____ \ / /_  \  /  | |  | | (_) \__ \ |_ 
  /_/    \_\____|  \/   |_|  |_|\___/|___/\__|
                                            '.PHP_EOL;
    echo '  Virtual Host Generator for Apache2'.PHP_EOL;
    echo '  a2vhost 1.0.0 b - (c) Hein Thanth'.PHP_EOL;
    echo '  https://github.com/heinthanth/a2vhost'.PHP_EOL;
    echo PHP_EOL;
}

function showUsage() {
    echo "  Usage: a2vhost [ -options ]".PHP_EOL.PHP_EOL;
    echo "  Example: a2vhost --ssl=true --root=/var/www/demo --domain=demo.local".PHP_EOL.PHP_EOL;
    echo "  Common Option:".PHP_EOL;
    echo "      --ssl    :  generate configuration for ssl-enabled vhost".PHP_EOL;
    echo "      --root   :  configure document root of vhost".PHP_EOL;
    echo "      --domain :  configure virtual domain name of vhost".PHP_EOL;
    echo PHP_EOL;
}

function run($cmd) {
    while (@ob_end_flush()) { /* end all output buffers if any */ }; 
    $proc = popen($cmd, 'r');
    while (!feof($proc)) {
        echo fread($proc, 4096);
        @flush();
    }
}

?>