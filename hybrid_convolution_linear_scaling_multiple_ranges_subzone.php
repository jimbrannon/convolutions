<?php
/*
 * this is like the hybrid linear scaled convolution function, but adds the ability to specify and use
 * multiple gwnetcu ranges ranges, each with it's own separate response function and linear scaling
 * 
 * this is like the simple convolution, but instead of being multiplicative,
 * the given response function is "scaled linearly" along a given line
 * 
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
function hybrid_convolution_linear_scaling_multiple_ranges_subzone($excitation_array=array(), $subzone_array=array(), $response_arrays=array(), $subtimestepcount=1, $linex_array=array(), $liney_array=array(), $lineslope_array=array(), $x_range_array=array()) {
	$result = array();
	$excitation_counter=0;
	foreach ($excitation_array as  $timestepindex=>$excitation) {
		$excitation_subzone = $subzone_array[$timestepindex];
		/*
		 * first figure out which of the multiple response functions to use
		 * based on the excitation
		 * (for rgdss, this would be the netgwcu excitation compared to the
		 *  predefined (during calibration) netgwcu ranges.
		 *  each range has it's own response function and linear scaling line)
		 */
		$x_range_ndx=null;
		foreach ($x_range_array as $x_range_ndx=>$x_range) {
			//print("$x_range_ndx $x_range[0] $x_range[1] \n");
			$min=$x_range[0];
			$max=$x_range[1];
			if (($excitation>=$min)&&($excitation<$max)) {
				$linex=$linex_array[$x_range_ndx];
				$liney=$liney_array[$x_range_ndx];
				$lineslope=$lineslope_array[$x_range_ndx];
				//print("$x_range_ndx $linex $liney $lineslope \n");
				$response_array=$response_arrays[$x_range_ndx];
			}
		}
		if (isset($x_range_ndx)) {
		} else {
			return null;
		}
		/*
		 * now we resume your regularly scheduled programming...
		 * BUT WITH THE SUBZONE EXCITATION!!
		 */
		
		/*
		 * determine a linear scaling fraction to use against the response function
		 * = ye (y for excitation) / liney
		 * find using the equation of the given line
		 * assumes the given response function represents the given x,y point
		 */
		//$ye = $liney + ($excitation-$linex)*$lineslope;
		$ye = $liney + ($excitation_subzone-$linex)*$lineslope;
		$linearscalefracton = $ye / $liney;
		if ($excitation_counter) {
		} else {
			$firsttimestepindex = $timestepindex;
		}
		$startingsubtimestep = ($timestepindex-$firsttimestepindex) * $subtimestepcount + 1;
		$response_counter=0;
		foreach ($response_array as $responseindex=>$responsevalue) {
			// subtract 1 because the response array index is one based
			if (array_key_exists ($startingsubtimestep+$responseindex-1,$result)) {
				$result[$startingsubtimestep+$responseindex-1] += $responsevalue*$linearscalefracton;
			} else {
				$result[$startingsubtimestep+$responseindex-1] = $responsevalue*$linearscalefracton;
			}
			++$response_counter;
		}
		++$excitation_counter;
	}
	return $result;
}
?>