<?php
/*
 * this like the simple convolution, but instead of being multiplicative,
 * the given response function is "scaled linearly" along a given line
 * 
 * for this simple routine, the time steps of all arrays must be the same!
 *   excitation_array needs to have an integer index
 *     (can be years, a month counter, etc. does not necessarily have to start with 1 or be continuous)
 *     for the first dimension and a real value for the second dimension
 *   response_array needs to have an arbitrary counting index as the first dimension
 *     and a real "response" value for the second dimension
 *   the result array will be the same type as first dimension of the excitation array
 *     (incremented as specified in the response array)
 *   to make things more readable for typical engineer users,
 *     the response_array will be ONE based
 */
function simple_convolution_linear_scaling($excitation_array=array(), $response_array=array(), $linex, $liney, $lineslope) {
	$result = array();
	foreach ($excitation_array as  $timestepindex=>$excitation) {
		/*
		 * determine a linear scaling fraction to use against the response function
		 * = ye (y for excitation) / liney
		 * find using the equation of the given line
		 * assumes the given response function represents the given x,y point
		 */
		$ye = $liney + ($excitation-$linex)*$lineslope;
		$linearscalefracton = $ye / $liney;
		foreach ($response_array as $responseindex=>$responsevalue) {
			// subtract 1 because the response array index is one based
			if (array_key_exists ($timestepindex+$responseindex-1,$result)) {
				$result[$timestepindex+$responseindex-1] += $responsevalue*$linearscalefracton;
			} else {
				$result[$timestepindex+$responseindex-1] = $responsevalue*$linearscalefracton;
			}
		}
	}
	return $result;
}
?>