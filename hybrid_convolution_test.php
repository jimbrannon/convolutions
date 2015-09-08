<?php
/*
 * test the hybrid convolution function
 */
include("hybrid_convolution.php");

$excitation_array=array(2001=>5.0,2002=>10.0,2003=>5.0);
print_r($excitation_array);
$response_array=array(1=>0.1,2=>0.5,3.>0.4);
print_r($response_array);
print_r(hybrid_convolution($excitation_array,$response_array,1));
?>