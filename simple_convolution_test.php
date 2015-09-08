<?php
/*
 * test the simple convolution function
 */
$excitation_array=array(2001=>5.0,2002=>10.0,2003=>5.0);
print_r($excitation_array);
$response_array=array(1=>0.1,2=>0.5,3.>0.4);
print_r($response_array);
print_r(simple_convolution($excitation_array,$response_array));

include "simple_convolution.php";
