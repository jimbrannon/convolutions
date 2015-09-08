<?php
/*
 * test the hybrid convolution with linear scaling function
 */
include("hybrid_convolution_linear_scaling_multiple_ranges.php");

/*
 * get data from pg
 */
$pgdb = "d7dev";
$pghost = "localhost";
$pgport = 5432;
$pguser = "drupal";
$pgpassword = "drupal_psql";
$pgconnectionstring = "dbname=$pgdb host=$pghost port=$pgport";
if (strlen($pguser)) {
	$pgconnectionstring .= " user=$pguser";
}
if (strlen($pgpassword)) {
	$pgconnectionstring .= " password=$pgpassword";
}
$pgconnection = pg_connect($pgconnectionstring);
if (!$pgconnection) {
	if ($logging) echo "Error: could not make database connection: ".pg_last_error($pgconnection);
	return false;
}

//excitation array
//$excitation_array=array(2001=>5.0,2002=>10.0,2003=>0.0,2004=>15.0);
$pgtable = "rg_response_zone_model_data_annual";
$results = pg_query($pgconnection, "SELECT nyear,netgwcutotal,gwrechargetotal FROM $pgtable WHERE model_version=4 AND nzone=1 ORDER BY nyear ASC");
while ($row = pg_fetch_row($results)) {
	$excitation_array[$row[0]] = $row[1]-$row[2];
}
print_r($excitation_array);

// response arrays
//$response_array=array(1=>5.0,2=>10.0,3=>15.0,4=>30.0,5=>25.0,6=>15.0);
$response_array=array();
$response_arrays=array();
$pgtable = "rg_response_function_data";
$results = pg_query($pgconnection, "SELECT timestep,rspfnvalue FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=1 ORDER BY timestep ASC");
while ($row = pg_fetch_row($results)) {
	$response_array[$row[0]] = $row[1];
}
$response_arrays[1]=$response_array;
$response_array=array();
$results = pg_query($pgconnection, "SELECT timestep,rspfnvalue FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=2 ORDER BY timestep ASC");
while ($row = pg_fetch_row($results)) {
	$response_array[$row[0]] = $row[1];
}
$response_arrays[2]=$response_array;
$response_array=array();
$results = pg_query($pgconnection, "SELECT timestep,rspfnvalue FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=3 ORDER BY timestep ASC");
while ($row = pg_fetch_row($results)) {
	$response_array[$row[0]] = $row[1];
}
$response_arrays[3]=$response_array;
print_r($response_arrays);

// subtimestep
$subtimestepcount=12;

//linear scaling lines
//$linex = 5.0;
//$liney = 100.0;
//$slope = 10.0;
$linex_array = array();
$liney_array = array();
$lineslope_array = array();
$xrange_array = array();
$pgtable = "rg_response_functions";
$results = pg_query($pgconnection, "SELECT xrange_ndx,xrange_min,xrange_max,line_xval,line_yval,line_slope FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=1");
while ($row = pg_fetch_row($results)) {
	$xrange_array[$row[0]] = array($row[1],$row[2]);
	$linex_array[$row[0]] = $row[3];
	$liney_array[$row[0]] = $row[4];
	$lineslope_array[$row[0]] = $row[5];
}
$results = pg_query($pgconnection, "SELECT xrange_ndx,xrange_min,xrange_max,line_xval,line_yval,line_slope FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=2");
while ($row = pg_fetch_row($results)) {
	$xrange_array[$row[0]] = array($row[1],$row[2]);
	$linex_array[$row[0]] = $row[3];
	$liney_array[$row[0]] = $row[4];
	$lineslope_array[$row[0]] = $row[5];
}
$results = pg_query($pgconnection, "SELECT xrange_ndx,xrange_min,xrange_max,line_xval,line_yval,line_slope FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=3");
while ($row = pg_fetch_row($results)) {
	$xrange_array[$row[0]] = array($row[1],$row[2]);
	$linex_array[$row[0]] = $row[3];
	$liney_array[$row[0]] = $row[4];
	$lineslope_array[$row[0]] = $row[5];
}
print_r($linex_array);
print_r($liney_array);
print_r($lineslope_array);
print_r($xrange_array);
print_r(hybrid_convolution_linear_scaling_multiple_ranges($excitation_array,$response_arrays,$subtimestepcount,$linex_array,$liney_array,$lineslope_array,$xrange_array));
?>