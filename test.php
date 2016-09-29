<?php

$time = microtime(true);
$memo = memory_get_usage();

require(__dir__.'/html-parser-v2/html-parser.php');

html_parser::$processContent = true;
$html = new html_parser(__dir__.'/test2.html');
$objs = $html->getObjects();
$r = $objs->find('html p{3}');
var_dump($r);

$time2 = microtime(true);
$memo2 = memory_get_usage();

echo PHP_EOL.'Tiempo: '.round(($time2-$time), 4).' Memoria: '.round((($memo2-$memo)/1024), 2).'Kb'.PHP_EOL;