<?php

require 'autoload.php';

use helpers\DataParser;

$parser = new DataParser($argv[1] ?? 'groups.csv', $argv[2] ?? 'products.csv');

if (!$parser->parseData()) {
    echo "Error code :".$parser->getErrorCode()." (".$parser->getErrorText().")".PHP_EOL;
} else {

    if (!$parser->flushResult($argv[3] ?? 'result.txt')) {
        echo "Error code :".$parser->getErrorCode()." (".$parser->getErrorText().")".PHP_EOL;
    }
}

