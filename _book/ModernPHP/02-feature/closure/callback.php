<?php
$numberPlusOne = array_map(function($number){
    return $number + 1;
}, [1,2,3]);

print_r($numberPlusOne);