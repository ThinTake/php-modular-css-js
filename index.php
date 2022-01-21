<?php

$text = "
    Start
    /*
        {{
            IMPORT   test,test1
        }}
    */
    End
";
// "\s*" for multiline and space
$pattern = "/\/\*\s*{{\s*IMPORT \s*(.*?)\s*}}\s*\*\//";

echo preg_replace_callback($pattern, function($m) {
    return rtrim($m[1],",");
}, $text);

//class completed, version 0.1