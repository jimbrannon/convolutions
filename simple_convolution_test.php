<?php
/*
 * test the simple convolution function
 */
// include "simple_convolution.php";
/*
 * 
 */
$excitation_array=array(2001=>5.0,2002=>10.0,2003=>5.0);
print_r($excitation_array);
$response_array=array(1=>0.1,2=>0.5,3.>0.4);
print_r($response_array);
print_r(simple_convolution($excitation_array,$response_array));

/*
 * for this simple routine, the time steps of all arrays must be the same!
 *   excitation_array needs to have an integer index
 *     (can be years, a month counter, etc. does not necessarily have to start with 1 or be continuous)
 *     for the first dimension and a real value for the second dimension
 *   response_array needs to have an arbitrary counting index as the fist dimension
 *     and a real "response" value for the second dimension
 *   the result array will be the same type as first dimension of the excitation array
 *     (incremented as specified in the response array)
 *   to make things more readable for typical engineer users,
 *     the response_array will be ONE based
 */
function simple_convolution($excitation_array=array(), $response_array=array()) {
	$result = array();
	foreach ($excitation_array as  $timestepindex=>$excitation) {
		foreach ($response_array as $responseindex=>$responsevalue) {
			// subtract 1 because the response array index is one based
			if (array_key_exists ($timestepindex+$responseindex-1,$result)) {
				$result[$timestepindex+$responseindex-1] += $responsevalue*$excitation;
			} else {
				$result[$timestepindex+$responseindex-1] = $responsevalue*$excitation;
			}
		}
	}
	return $result;
}
