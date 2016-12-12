<?php
/**
 * Created by PhpStorm.
 * User: takamura
 * Date: 13.01.15
 * Time: 12:47
 */

//if (function_exists('xdebug_disable')) {
//    xdebug_disable();
//}

function dd(){
    echo '<pre>';
    $list = func_get_args();
    foreach ($list as $value) {
        var_dump($value);
        echo '<br/>';
    }
    echo '</pre>';
    die();
}

function dp(){
    echo '<pre>';
    $list = func_get_args();
    foreach ($list as $value) {
        print_r($value);
        echo '<br/>';
    }
    echo '</pre>';
    die();
}
