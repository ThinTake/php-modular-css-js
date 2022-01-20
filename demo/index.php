<?php
include '../modular-css-js.class.php';

$test = new ModularCssJs('modules', 'assets', true);

$files = $test->get('hello', ['button-primary', 'tooltip', 'tooltip-dark']);

var_dump($files);

echo 'done';