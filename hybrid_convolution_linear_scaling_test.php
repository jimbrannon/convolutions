<?php
/*
 * test the hybrid convolution with linear scaling function
 */
include("hybrid_convolution_linear_scaling.php");

$excitation_array=array(2001=>5.0,2002=>10.0);
print_r($excitation_array);
$response_array=array(1=>20.0,2=>50.0,3=>30.0);
print_r($response_array);
$subtimestepcount=1;
$linex = 5.0;
$liney = 100.0;
$slope = 10.0;
print_r(hybrid_convolution_linear_scaling($excitation_array,$response_array,$subtimestepcount,$linex,$liney,$slope));
?>