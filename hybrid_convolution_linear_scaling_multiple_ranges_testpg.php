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

define("RGRESPFNFTABLE","rgrespfntable");
define("RGRESPFNDATATABLE","rgrespfndatatable");
define("RGGWNETCUYRTABLE","rggwnetcuyrtable");

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
$options[PGDB]="d7dev";
$options[PGHOST]="localhost";
$options[PGPORT]=5432;

$options[RGMODELVERSION]=4; // 4 is 6P97
$options[RGRESPONSEZONE]=1; // 1 is SD1
$options[RGSTREAMREACH]=1; // 1 is RG1 (del norte to excelsior)
$options[RGRESPFNFTABLE]="rg_response_functions";
$options[RGRESPFNDATATABLE]="rg_response_function_data";
$options[RGGWNETCUYRTABLE]="rg_response_zone_model_data_annual";

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
$debugging_arg = getargs (DEBUGGING,$debugging);
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
$logging_arg = getargs (LOGGING,$logging);
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
$pguser_arg = getargs (PGUSER,$pguser);
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
$pgpassword_arg = getargs (PGPASSWORD,$pgpassword);
if ($debugging) echo "pgpassword_arg: $pgpassword_arg \n";
if (strlen(trim($pgpassword_arg))) {
	$pgpassword = trim($pgpassword_arg);
	$options[PGPASSWORD] = $pgpassword;
	if ($debugging) echo "pgpassword final: $pgpassword \n";
} else {
	if ($debugging) echo "pgpassword final: $pgpassword \n";
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
$pgdb_arg = getargs (PGDB,$pgdb);
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
$pghost_arg = getargs (PGHOST,$pghost);
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
$pgport_arg = getargs (PGPORT,$pgport);
if ($debugging) echo "pgport_arg: $pgport_arg \n";
if (strlen(trim($pgport_arg))) {
	$pgport = intval($pgport_arg);
	$options[PGPORT] = $pgport;
	if ($debugging) echo "pgport final: $pgport \n";
} else {
	if ($debugging) echo "pgport final: $pgport \n";
}

/*
 * get the rg model version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGMODELVERSION,$options)) {
	$rgmodelversion = $options[RGMODELVERSION];
} else {
	// we can not set a default for this
	$rgmodelversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgmodelversion default: $rgmodelversion \n";
$rgmodelversion_arg = getargs (RGMODELVERSION,$rgmodelversion);
if ($debugging) echo "rgmodelversion_arg: $rgmodelversion_arg \n";
if (strlen(trim($rgmodelversion_arg))) {
	$rgmodelversion = intval($rgmodelversion_arg);
}
if ($rgmodelversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgmodelversion: $rgmodelversion \n";
	$options[RGMODELVERSION] = $rgmodelversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgmodelversion: $rgmodelversion exiting \n";
	if ($debugging) echo "invalid rgmodelversion: $rgmodelversion exiting \n";
	return;
}
/*
 * get the rg response zone arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRESPONSEZONE,$options)) {
	$rgresponsezone = $options[RGRESPONSEZONE];
} else {
	// we can not set a default for this
	$rgresponsezone = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgresponsezone default: $rgresponsezone \n";
$rgresponsezone_arg = getargs (RGRESPONSEZONE,$rgresponsezone);
if ($debugging) echo "rgresponsezone_arg: $rgresponsezone_arg \n";
if (strlen(trim($rgresponsezone_arg))) {
	$rgresponsezone = intval($rgresponsezone_arg);
}
if ($rgresponsezone > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgresponsezone: $rgresponsezone \n";
	$options[RGRESPONSEZONE] = $rgresponsezone;
} else {
	// can not proceed without this
	if ($logging) echo "invalid rgresponsezone: $rgresponsezone exiting \n";
	if ($debugging) echo "invalid rgresponsezone: $rgresponsezone exiting \n";
	return;
}
/*
 * get the rg stream reach arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGSTREAMREACH,$options)) {
	$rgstreamreach = $options[RGSTREAMREACH];
} else {
	// we can not set a default for this
	$rgstreamreach = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgstreamreach default: $rgstreamreach \n";
$rgstreamreach_arg = getargs (RGSTREAMREACH,$rgstreamreach);
if ($debugging) echo "rgstreamreach_arg: $rgstreamreach_arg \n";
if (strlen(trim($rgstreamreach_arg))) {
	$rgstreamreach = intval($rgstreamreach_arg);
}
if ($rgstreamreach > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgstreamreach: $rgstreamreach \n";
	$options[RGSTREAMREACH] = $rgstreamreach;
} else {
	// can not proceed without this
	if ($logging) echo "invalid rgstreamreach: $rgstreamreach exiting \n";
	if ($debugging) echo "invalid rgstreamreach: $rgstreamreach exiting \n";
	return;
}

/*
 * get the rgrespfntable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRESPFNFTABLE,$options)) {
	$rgrespfntable = $options[RGRESPFNFTABLE];
} else {
	// we can NOT set a default for this
	$rgrespfntable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgrespfntable default: $rgrespfntable \n";
$rgrespfntable_arg = getargs (RGRESPFNFTABLE,$rgrespfntable);
if ($debugging) echo "rgrespfntable_arg: $rgrespfntable_arg \n";
if (strlen(trim($rgrespfntable_arg))) {
	$rgrespfntable = trim($rgrespfntable_arg);
}
if (strlen($rgrespfntable=trim($rgrespfntable))) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgrespfntable: $rgrespfntable \n";
	$options[RGRESPFNFTABLE] = $rgrespfntable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgrespfntable exiting \n";
	if ($debugging) echo "missing rgrespfntable exiting \n";
	return;
}


/*
 * make the pg coonnection
 */
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

//set up the excitation array
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