<?php
/*
 * test the hybrid convolution with linear scaling function
 */
include("misc_io.php");
include("hybrid_convolution_linear_scaling_multiple_ranges.php");

/*
 * constants - do not modify
 */

define("DEBUGGING","debugging");
define("LOGGING","logging");

define("PGUSER","pguser");
define("PGPASSWORD","pgpassword");
define("PGTABLE","pgtable");
define("PGHOST","pghost");
define("PGDB","pgdb");
define("PGPORT","pgport");

define("RGMODELVERSION","rgmodelversion");
define("RGRESPONSEZONE","rgresponsezone");
define("RGSTREAMREACH","rgstreamreach");

/*
 * argument defaults
 *   these are OK to modify, as long as the values are valid
 * much like pg2gviz
 * in other words, defaults get used unless over-ridden by command line args
 * the difference is they all get stuffed into an options array, makes for much cleaner code
 */
$options[DEBUGGING]=false;
$options[LOGGING]=true;

$options[PGUSER]="drupal";
$options[PGPASSWORD]="drupal_psql";
$options[PGTABLE]="empty_dummy";
$options[PGDB]="d7dev";
$options[PGHOST]="localhost";
$options[PGPORT]=5432;

$options[RGMODELVERSION]=4; // 4 is 6P97
$options[RGRESPONSEZONE]=1; // 1 is SD1
$options[RGSTREAMREACH]=1; // 1 is RG1 (del norte to excelsior)

/*
 * handle the args right here in the wrapper
 */

/*
 * get the debugging arg
 */
if (array_key_exists(DEBUGGING,$options)) {
	$debugging = $options[DEBUGGING];
} else {
	$debugging = false;
}
if ($debugging) echo "debugging default: $debugging \n";
$debugging_arg = getargs ("debugging",$debugging);
if ($debugging) echo "debugging_arg: $debugging_arg \n";
if (strlen(trim($debugging_arg))) {
	$debugging = strtobool($debugging_arg);
	$options[DEBUGGING] = $debugging;
}
if ($debugging) echo "debugging final: $debugging \n";
/*
 * get the logging arg
 */
if (array_key_exists(LOGGING,$options)) {
	$logging = $options[LOGGING];
} else {
	$logging = false;
}
if ($debugging) echo "logging default: $logging \n";
$logging_arg = getargs ("logging",$logging);
if ($debugging) echo "logging_arg: $logging_arg \n";
if (strlen(trim($logging_arg))) {
	$logging = strtobool($logging_arg);
	$options[LOGGING] = $logging;
}
if ($debugging) echo "logging final: $logging \n";
/*
 * get the pguser arg
 */
if (array_key_exists(PGUSER,$options)) {
	$pguser = $options[PGUSER];
} else {
	$pguser = "";
}
if ($debugging) echo "pguser default: $pguser \n";
$pguser_arg = getargs ("pguser",$pguser);
if ($debugging) echo "pguser_arg: $pguser_arg \n";
if (strlen(trim($pguser_arg))) {
	$pguser = trim($pguser_arg);
	$options[PGUSER] = $pguser;
	if ($debugging) echo "pguser final: $pguser \n";
} else {
	if ($debugging) echo "pguser final: $pguser \n";
}
/*
 * get the pgpassword arg
 */
if (array_key_exists(PGPASSWORD,$options)) {
	$pgpassword = $options[PGPASSWORD];
} else {
	$pgpassword = "";
}
if ($debugging) echo "pgpassword default: $pgpassword \n";
$pgpassword_arg = getargs ("pgpassword",$pgpassword);
if ($debugging) echo "pgpassword_arg: $pgpassword_arg \n";
if (strlen(trim($pgpassword_arg))) {
	$pgpassword = trim($pgpassword_arg);
	$options[PGPASSWORD] = $pgpassword;
	if ($debugging) echo "pgpassword final: $pgpassword \n";
} else {
	if ($debugging) echo "pgpassword final: $pgpassword \n";
}
/*
 * get the pgtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(PGTABLE,$options)) {
	$pgtable = $options[PGTABLE];
} else {
	// we can NOT set a default for this so the arg better have something!
	$pgtable = "";
}
if ($debugging) echo "pgtable default: $pgtable \n";
$pgtable_arg = getargs ("pgtable",$pgtable);
if ($debugging) echo "pgtable_arg: $pgtable_arg \n";
if (strlen(trim($pgtable_arg))) {
	$pgtable = trim($pgtable_arg);
	$options[PGTABLE] = $pgtable;
	if ($debugging) echo "pgtable final: $pgtable \n";
} else {
	if (strlen(trim($pgtable))) {
		if ($debugging) echo "pgtable final: $pgtable \n";
	} else {
		// we can NOT proceed without a pgtable!!
		if ($logging) echo "Error: Missing pgtable. \n";
		if ($debugging) print_r($options);
		return false;
	}
}
/*
 * get the pgdb arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(PGDB,$options)) {
	$pgdb = $options[PGDB];
} else {
	// we can NOT set a default for this so the arg better have something!
	$pgdb = "";
}
if ($debugging) echo "pgdb default: $pgdb \n";
$pgdb_arg = getargs ("pgdb",$pgdb);
if ($debugging) echo "pgdb_arg: $pgdb_arg \n";
if (strlen(trim($pgdb_arg))) {
	$pgdb = trim($pgdb_arg);
	$options[PGDB] = $pgdb;
	if ($debugging) echo "pgdb final: $pgdb \n";
} else {
	if (strlen(trim($pgdb))) {
		if ($debugging) echo "pgdb final: $pgdb \n";
	} else {
		// we can NOT proceed without a pgdb!!
		if ($logging) echo "Error: Missing pgdb. \n";
		if ($debugging) print_r($options);
		return false;
	}
}
/*
 * get the pghost arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(PGHOST,$options)) {
	$pghost = $options[PGHOST];
} else {
	// we can set a default for this
	$pghost = "localhost";
}
if ($debugging) echo "pghost default: $pghost \n";
$pghost_arg = getargs ("pghost",$pghost);
if ($debugging) echo "pghost_arg: $pghost_arg \n";
if (strlen(trim($pghost_arg))) {
	$pghost = trim($pghost_arg);
	$options[PGHOST] = $pghost;
	if ($debugging) echo "pghost final: $pghost \n";
} else {
	if ($debugging) echo "pghost final: $pghost \n";
}
/*
 * get the pgport arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(PGPORT,$options)) {
	$pgport = $options[PGPORT];
} else {
	// we can set a default for this
	$pgport = 5432;
}
if ($debugging) echo "pgport default: $pgport \n";
$pgport_arg = getargs ("pgport",$pgport);
if ($debugging) echo "pgport_arg: $pgport_arg \n";
if (strlen(trim($pgport_arg))) {
	$pgport = intval($pgport_arg);
	$options[PGPORT] = $pgport;
	if ($debugging) echo "pgport final: $pgport \n";
} else {
	if ($debugging) echo "pgport final: $pgport \n";
}

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
	return;
}

//excitation array
//$excitation_array=array(2001=>5.0,2002=>10.0,2003=>0.0,2004=>15.0);
$pgtable = "rg_response_zone_model_data_annual";
$results = pg_query($pgconnection, "SELECT nyear,netgwcutotal,gwrechargetotal FROM $pgtable WHERE model_version=4 AND nzone=1 ORDER BY nyear ASC");
while ($row = pg_fetch_row($results)) {
	$excitation_array[$row[0]] = $row[1]-$row[2];
}
//print_r($excitation_array);

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
//print_r($response_arrays);

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
//print_r($linex_array);
//print_r($liney_array);
//print_r($lineslope_array);
//print_r($xrange_array);
$results = hybrid_convolution_linear_scaling_multiple_ranges($excitation_array,$response_arrays,$subtimestepcount,$linex_array,$liney_array,$lineslope_array,$xrange_array);
?>