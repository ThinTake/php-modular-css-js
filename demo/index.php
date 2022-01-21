<?php
include '../modular-css-js.class.php';

$test = new ModularCssJs('modules', 'assets', false);

$files = $test->get('home-page', ['button-primary', 'tooltip', 'tooltip-dark']);

var_dump($files);

echo 'done';