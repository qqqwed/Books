<?php
/* 简单的一个生成器
 * @Author: Martini Dong 
 * @Date: 2019-04-11 22:39:01 
 * @Last Modified by: Martini Dong
 * @Last Modified time: 2019-04-11 22:53:03
 */

function myGenerator() {
    yield 'value1';
    yield 'value2';
    yield 'value3';
}

foreach (myGenerator() as $value) {
    echo $value, PHP_EOL;
}