#!/usr/bin/env php
<?php

showBanner();

if ($argc > 1) {
    print_r($argv);
} else {
    showUsage();
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
    echo "  Usage: a2vhost [ -option ] [ --settings ]".PHP_EOL;
}

// ending white space line of shell
echo PHP_EOL.PHP_EOL;

?>