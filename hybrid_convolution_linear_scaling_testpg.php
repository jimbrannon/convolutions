<?php
/*
 * test the hybrid convolution with linear scaling function
 */
include("hybrid_convolution_linear_scaling.php");

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
$pgtable = "rg_response_zone_model_data_annual";
$results = pg_query($pgconnection, "SELECT nyear,netgwcutotal,gwrechargetotal FROM $pgtable WHERE model_version=4 AND nzone=1 ORDER BY nyear ASC");
while ($row = pg_fetch_row($results)) {
	$excitation_array[$row[0]] = $row[1]-$row[2];
}
//$excitation_array=array(2001=>5.0,2002=>10.0,2003=>0.0,2004=>15.0);
print_r($excitation_array);
$pgtable = "rg_response_function_data";
$results = pg_query($pgconnection, "SELECT timestep,rspfnvalue FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=2 ORDER BY timestep ASC");
while ($row = pg_fetch_row($results)) {
	$response_array[$row[0]] = $row[1];
}
//$response_array=array(1=>5.0,2=>10.0,3=>15.0,4=>30.0,5=>25.0,6=>15.0);
print_r($response_array);
$subtimestepcount=12;
$pgtable = "rg_response_functions";
$results = pg_query($pgconnection, "SELECT line_xval,line_yval,line_slope FROM $pgtable WHERE model_version=4 AND nzone=1 AND nreach=1 AND nrspfn=1 AND xrange_ndx=2");
while ($row = pg_fetch_row($results)) {
	$linex = $row[0];
	$liney = $row[1];
	$slope = $row[2];
}
//$linex = 5.0;
//$liney = 100.0;
//$slope = 10.0;
print_r(hybrid_convolution_linear_scaling($excitation_array,$response_array,$subtimestepcount,$linex,$liney,$slope));
?>