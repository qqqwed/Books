<?php
$closure = function ($name){
    return sprintf('Hello %s', $name);
};

echo get_class($closure);   //输出 "Closure"

echo $closure('Martini');   // 输出 "Hello Mariti"
