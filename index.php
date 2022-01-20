<?php
include 'css.class.php';

$test = new CSS('modules', 'storage');

$files = $test->get('hello', ['button-primary', 'tooltip', 'tooltip-dark']);

var_dump($files);

echo 'done';