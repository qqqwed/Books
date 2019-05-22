<?php
function enclodePerson($name){
    return function($doCommand) use($name) {
        return sprintf('%s, %s', $name, $doCommand);
    };
}

// 把字符串"Jack"封装到闭包中
$jack = enclodePerson('Jack');

// 传入参数,调用闭包
echo $jack('get me sweet tea!');    // 输出 "Jack, get me sweet tea!"