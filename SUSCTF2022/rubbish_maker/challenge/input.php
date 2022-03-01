<?php
error_reporting(0);
$a = $b = $c = $d = $e = $f = null;
if (function_exists('phpdbg_clear')) {
    phpdbg_clear();
}
$a = $_GET['%s'];
$b = $_GET['%s'];
$c = $_GET['%s'];
if (function_exists('phpdbg_prompt')) {
    phpdbg_prompt(urldecode('error occurred'));
}
if (php_sapi_name() == 'phpdbg') {
    return;
}

if ($a + $b + $c == %d) {
    $d = $_GET[$a];
}
if (!$_SERVER["REMOTE_ADDR"]) {
    return;
}

if ($a + $b + $c == %d) {
    $e = $_GET[$b];
}else{
    $e = $a+$b+$c;
}

if (function_exists('xdebug_is_debugger_active')) {
    if (xdebug_is_debugger_active() && function_exists('xdebug_break')) {
        while (true) {
            eval('while(true){xdebug_break();}');
            xdebug_break();
        }
    }
}
switch ($_GET[$d]) {
    case 1:
        $d = "/flag";
        break;
    case 2:
        $e = $_GET[$b];
        break;
    case 3:
        eval($e);
        break;
    case 4:
        $f = "%s";
        break;
    default:
        break;
}
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

$t0 = microtime() * 1000;
eval('1+1;');
$t1 = microtime() * 1000;
if ($t1 - $t0 > 100) {
    return;
}

if ($_GET[$f] && $e == $d && $a>1000 && !strpos($_GET[$f], "/flag")) {
    readfile($_GET[$f]);
}

if ($a - $b == %d) {
    $d = $_GET[$a];
}

if(substr($a,$b) == 1){
    $e = $_GET[$a];
}

$f = "%s";

if (function_exists('xdebug_is_enabled')) {
    echo 'error occurred';
    die(0);
}
if (function_exists("xdebug_get_tracefile_name")) {
    $filename = xdebug_get_tracefile_name();
    if ($filename !== false) {
        file_put_contents(xdebug_stop_trace(), 'error occurred');
    }
}