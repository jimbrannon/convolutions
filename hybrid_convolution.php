<?php
/*
 * for this routine, the time steps of the response array are substeps of the excitation array.
 * this is a special case created for the RGDSS project
 *   the excitation (gwnetcu) are annual values and have time steps of years,
 *     but the response function has time steps of months
 *     because of this, the output array will start with index 1 starting in the first subtimestep (eg month)
 *     of the first timestep in the excitation array (eg year)
 *   excitation_array needs to have an integer index
 *     (can be years, a month counter, etc. does not necessarily have to start with 1 or be continuous)
 *     for the first dimension and a real value for the second dimension
 *   response_array needs to have an arbitrary counting index as the fist dimension
 *     and a real "response" value for the second dimension
 *   the result array will be in the same time step as the RESPONSE array
 *   to make things more readable for typical engineer users,
 *     the response_array will be ONE based
 */
function hybrid_convolution($excitation_array=array(), $response_array=array(), $subtimestepcount=1) {
	$result = array();
	foreach ($excitation_array as  $timestepindex=>$excitation) {
		if (!isset($firsttimestepindex)) $firsttimestepindex = $timestepindex;
		$startingsubtimestep = ($timestepindex-$firsttimestepindex) * $subtimestepcount + 1;
		foreach ($response_array as $responseindex=>$responsevalue) {
			// subtract 1 because the response array index is one based
			if (array_key_exists ($startingsubtimestep+$responseindex-1,$result)) {
				$result[$startingsubtimestep+$responseindex-1] += $responsevalue*$excitation;
			} else {
				$result[$startingsubtimestep+$responseindex-1] = $responsevalue*$excitation;
			}
		}
	}
	return $result;
}
?>