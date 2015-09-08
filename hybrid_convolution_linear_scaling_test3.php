<?php
/*
 * test the hybrid convolution with linear scaling function
 */
include("hybrid_convolution_linear_scaling.php");

$excitation_array=array(2001=>5.0,2002=>10.0,2003=>0.0,2004=>15.0);
print_r($excitation_array);
$response_array=array(1=>5.0,2=>10.0,3=>15.0,4=>30.0,5=>25.0,6=>15.0);
print_r($response_array);
$subtimestepcount=2;
$linex = 5.0;
$liney = 100.0;
$slope = 10.0;
print_r(hybrid_convolution_linear_scaling($excitation_array,$response_array,$subtimestepcount,$linex,$liney,$slope));
?>