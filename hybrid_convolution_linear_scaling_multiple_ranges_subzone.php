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
 *   excitation_array needs to have a sequential integer index
 *     (can be years, a month counter, etc. does not necessarily have to start with 1 or be continuous)
 *     for the first dimension and a real value for the second dimension
 *   response_array needs to have an arbitrary counting index as the first dimension
 *     and a real "response" value for the second dimension
 *   the result array will be in the same time step as the RESPONSE array
 *   to make things more readable for typical engineer users,
 *     the output, the response_array, will be ONE based (the response array index starts at 1, not 0)
 */
function hybrid_convolution_linear_scaling_multiple_ranges_subzone($zone_grpval_array,
		$zone_gwcu_array, $zone_recharge_array, $subzone_gwcu_array, $subzone_recharge_array,
		$response_arrays, $subtimestepcount=1, $linex_array, $liney_array, $lineslope_array,
		$grp_range_array) {
	$debugging = false;
	if ($debugging) {
		print_r($zone_grpval_array);
		print_r($zone_gwcu_array);
		print_r($zone_recharge_array);
		print_r($subzone_gwcu_array);
		print_r($subzone_recharge_array);
	}
	$result = array();
	$excitation_counter=0;
	//foreach ($zone_gwcu_array as  $timestepindex=>$zone_gwcu) {
	/*
	 * loop through the annual time steps in the $zone_grpval_array array
	 * assumes all the other arrays have the same time steps
	 */
	foreach ($zone_grpval_array as  $timestepindex=>$zone_grpval) {
		//$excitation_zone = $zone_gwcu_array[$timestepindex]-$zone_recharge_array[$timestepindex];
		//$excitation_subzone = $subzone_gwcu_array[$timestepindex]-$subzone_recharge_array[$timestepindex];
		$zone_netgwcu = $zone_gwcu_array[$timestepindex]-$zone_recharge_array[$timestepindex];
		$subzone_netgwcu = $subzone_gwcu_array[$timestepindex]-$subzone_recharge_array[$timestepindex];
		/*
		 * first figure out which of the multiple response functions to use
		 * based on this zone's grouping value (zone_grpval)
		 * could be gwnetcu or streamflow ranges, for example, 
		 * (for rgdss, this is set up in the calibration phase)
		 *  each range has it's own response function and linear scaling line
		 */
		$linex=null;
		$liney=null;
		$lineslope=null;
		$response_array=null;
		/*
		 * loop through the grouping ranges
		 * the last one that contains the value, $zone_grpval, will be used
		 * this determines which response function and response function line will be used
		 */
		foreach ($grp_range_array as $grp_range_ndx=>$grp_range) {
			//print("$grp_range_ndx $grp_range[0] $grp_range[1] \n");
			$min=$grp_range[0];
			$max=$grp_range[1];
			if (($zone_grpval>=$min)&&($zone_grpval<=$max)) {
				$linex=$linex_array[$grp_range_ndx];
				$liney=$liney_array[$grp_range_ndx];
				$lineslope=$lineslope_array[$grp_range_ndx];
				//print("$grp_range_ndx $linex $liney $lineslope \n");
				$response_array=$response_arrays[$grp_range_ndx];
			}
		}
		if (!(isset($linex)&&isset($liney)&&isset($lineslope)&&isset($response_array))) {
			return null;
		}
		/*
		 * determine a linear "scaling fraction" to use against the response function
		 * the response function must match the linex and liney values, so
		 *   the response function 20 yr str depl (volume under the curve) = liney,
		 * the "scaling fraction" is the ratio between the response function liney
		 *   and the depletion for the given excitation (gwnetcu) for the subzone
		 *   "scaling fraction" = y_subzonenetgwcu (subzone 20 yr str depl volume) / liney
		 * find y_subzonenetgwcu using the equation of the given line
		 */
		//$ye = $liney + ($excitation-$linex)*$lineslope;
		// the following is the current DWR approach - assumes the line goes through the x,y origin
		//$ye = $liney + ($excitation_subzone-$linex)*$lineslope;
		/*
		 * the following algorithm works EVEN WHEN THE LINE DOES NOT GO THROUGH THE ORIGIN
		 * but also gives the same results as the DWR method when the line DOES go through the origin
		 * define "works" as prorating correctly (linearly) between subzones such that
		 *   the str depletions of the parts (subzones) add up to the whole (zone)
		 */
		// the 20 yr total str depl for the zone netgwcu
		$y_zonenetgwcu = $liney + ($zone_netgwcu-$linex)*$lineslope;
		// the 20 yr total str depl for the zone gwcu (no recharge)
		$y_zonegwcu = $liney + ($zone_gwcu_array[$timestepindex]-$linex)*$lineslope;
		// the 20 yr total str accr for the zone recharge
		$ydiff_zonerecharge = $y_zonegwcu - $y_zonenetgwcu; //should be 0 or positive
		// calculate the subzone gwcu str depl (no recharge) as a percentage of the zone str depl
		$y_subzonegwcu = $y_zonegwcu * ($subzone_gwcu_array[$timestepindex]/$zone_gwcu_array[$timestepindex]);
		// calculate the subsubzone recharge str accr as a percentage of the zone recharge str accr
		if ($zone_recharge_array[$timestepindex]>0.0) {
			$ydiff_subzonerecharge = $ydiff_zonerecharge * ($subzone_recharge_array[$timestepindex]/$zone_recharge_array[$timestepindex]);
		} else {
			$ydiff_subzonerecharge = 0.0;
		}
		// the subzone 20 yr str depl is pumping depl minus recharge accretions
		$y_subzonenetgwcu = $y_subzonegwcu - $ydiff_subzonerecharge;
		$linearscalefracton = $y_subzonenetgwcu / $liney;
		
		if (!$excitation_counter) {
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
