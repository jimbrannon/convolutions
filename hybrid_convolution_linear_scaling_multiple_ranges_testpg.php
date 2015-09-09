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
define("RGRESPFNVERSION","rgrespfnversion");
define("RGSUBTIMESTEPCOUNT","rgsubtimestepcount");

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
$options[RGRESPFNVERSION]=1; // 1 would be the usual, the officially released one from DWR, unless there happen to multiple versions in the database
//$subtimestepcount
$options[RGSUBTIMESTEPCOUNT]=12; // 12 would be the usual, years to months

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
if (strlen($debugging_arg=trim($debugging_arg))) {
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
if (strlen($logging_arg=trim($logging_arg))) {
	$logging = strtobool($logging_arg);
	$options[LOGGING] = $logging;
}
if ($debugging) echo "logging final: $logging \n";
/*
 * get the pguser arg
 */
if (array_key_exists(PGUSER,$options)) {
	$pguser = trim($options[PGUSER]);
} else {
	$pguser = ""; // set it to an invalid value and check later
}
if ($debugging) echo "pguser default: $pguser \n";
$pguser_arg = getargs (PGUSER,$pguser);
if ($debugging) echo "pguser_arg: $pguser_arg \n";
if (strlen(trim($pguser_arg))) {
	$pguser = trim($pguser_arg);
}
if (strlen($pguser)) {
	// a potentially valid value, use it
	if ($debugging) echo "final pguser: $pguser \n";
	$options[PGUSER] = $pguser;
} else {
	// can not proceed without this
	if ($logging) echo "missing pguser exiting \n";
	if ($debugging) echo "missing pguser exiting \n";
	return;
}
/*
 * get the pgpassword arg
 */
if (array_key_exists(PGPASSWORD,$options)) {
	$pgpassword = trim($options[PGPASSWORD]);
} else {
	$pgpassword = ""; // set it to an invalid value and check later
}
if ($debugging) echo "pgpassword default: $pgpassword \n";
$pgpassword_arg = getargs (PGPASSWORD,$pgpassword);
if ($debugging) echo "pgpassword_arg: $pgpassword_arg \n";
if (strlen(trim($pgpassword_arg))) {
	$pgpassword = trim($pgpassword_arg);
}
if (strlen($pgpassword)) {
	// a potentially valid value, use it
	if ($debugging) echo "final pgpassword: $pgpassword \n";
	$options[PGPASSWORD] = $pgpassword;
} else {
	// can not proceed without this
	if ($logging) echo "missing pgpassword exiting \n";
	if ($debugging) echo "missing pgpassword exiting \n";
	return;
}
/*
 * get the pgdb arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(PGDB,$options)) {
	$pgdb = trim($options[PGDB]);
} else {
	// we can NOT set a default for this
	$pgdb = ""; // set it to an invalid value and check later
}
if ($debugging) echo "pgdb default: $pgdb \n";
$pgdb_arg = getargs (PGDB,$pgdb);
if ($debugging) echo "pgdb_arg: $pgdb_arg \n";
if (strlen(trim($pgdb_arg))) {
	$pgdb = trim($pgdb_arg);
}
if (strlen($pgdb)) {
	// a potentially valid value, use it
	if ($debugging) echo "final pgdb: $pgdb \n";
	$options[PGDB] = $pgdb;
} else {
	// can not proceed without this
	if ($logging) echo "missing pgdb exiting \n";
	if ($debugging) echo "missing pgdb exiting \n";
	return;
}
/*
 * get the pghost arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(PGHOST,$options)) {
	$pghost = trim($options[PGHOST]);
} else {
	// we can set a default for this
	$pghost = "localhost";
}
if ($debugging) echo "pghost default: $pghost \n";
$pghost_arg = getargs (PGHOST,$pghost);
if ($debugging) echo "pghost_arg: $pghost_arg \n";
if (strlen(trim($pghost_arg))) {
	$pghost = trim($pghost_arg);
}
if (strlen($pghost)) {
	// a potentially valid value, use it
	if ($debugging) echo "final pghost: $pghost \n";
	$options[PGHOST] = $pghost;
} else {
	// can not proceed without this
	if ($logging) echo "missing pghost exiting \n";
	if ($debugging) echo "missing pghost exiting \n";
	return;
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
}
if ($pgport > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final pgport: $pgport \n";
	$options[PGPORT] = $pgport;
} else {
	// can not proceed without this
	if ($logging) echo "invalid pgport: $pgport exiting \n";
	if ($debugging) echo "invalid pgport: $pgport exiting \n";
	return;
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
if (strlen($rgmodelversion_arg=trim($rgmodelversion_arg))) {
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
if (strlen($rgresponsezone_arg=trim($rgresponsezone_arg))) {
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
if (strlen($rgstreamreach_arg=trim($rgstreamreach_arg))) {
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
 * get the rg response function version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRESPFNVERSION,$options)) {
	$rgrespfnversion = $options[RGRESPFNVERSION];
} else {
	// we can (with trepidation) set a default for this
	$rgrespfnversion = 1;
}
if ($debugging) echo "rgrespfnversion default: $rgrespfnversion \n";
$rgrespfnversion_arg = getargs (RGRESPFNVERSION,$rgrespfnversion);
if ($debugging) echo "rgrespfnversion_arg: $rgrespfnversion_arg \n";
if (strlen($rgrespfnversion_arg=trim($rgrespfnversion_arg))) {
	$rgrespfnversion = intval($rgrespfnversion_arg);
}
if ($rgrespfnversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgrespfnversion: $rgrespfnversion \n";
	$options[RGRESPFNVERSION] = $rgrespfnversion;
} else {
	// can not proceed without this
	if ($logging) echo "invalid rgrespfnversion: $rgrespfnversion exiting \n";
	if ($debugging) echo "invalid rgrespfnversion: $rgrespfnversion exiting \n";
	return;
}

/*
 * get the rgrespfntable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRESPFNFTABLE,$options)) {
	$rgrespfntable = trim($options[RGRESPFNFTABLE]);
} else {
	// we can NOT set a default for this
	$rgrespfntable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgrespfntable default: $rgrespfntable \n";
$rgrespfntable_arg = getargs (RGRESPFNFTABLE,$rgrespfntable);
if ($debugging) echo "rgrespfntable_arg: $rgrespfntable_arg \n";
if (strlen($rgrespfntable_arg=trim($rgrespfntable_arg))) {
	$rgrespfntable = $rgrespfntable_arg;
}
if (strlen($rgrespfntable)) {
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
 * get the rgrespfntable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRESPFNDATATABLE,$options)) {
	$rgrespfndatatable = trim($options[RGRESPFNDATATABLE]);
} else {
	// we can NOT set a default for this
	$rgrespfndatatable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgrespfndatatable default: $rgrespfndatatable \n";
$rgrespfndatatable_arg = getargs (RGRESPFNDATATABLE,$rgrespfndatatable);
if ($debugging) echo "rgrespfndatatable_arg: $rgrespfndatatable_arg \n";
if (strlen($rgrespfndatatable_arg=trim($rgrespfndatatable_arg))) {
	$rgrespfndatatable = $rgrespfndatatable_arg;
}
if (strlen($rgrespfndatatable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgrespfndatatable: $rgrespfndatatable \n";
	$options[RGRESPFNDATATABLE] = $rgrespfndatatable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgrespfndatatable exiting \n";
	if ($debugging) echo "missing rgrespfndatatable exiting \n";
	return;
}
/*
 * get the rggwnetcuyrtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGGWNETCUYRTABLE,$options)) {
	$rggwnetcuyrtable = trim($options[RGGWNETCUYRTABLE]);
} else {
	// we can NOT set a default for this
	$rggwnetcuyrtable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rggwnetcuyrtable default: $rggwnetcuyrtable \n";
$rggwnetcuyrtable_arg = getargs (RGGWNETCUYRTABLE,$rggwnetcuyrtable);
if ($debugging) echo "rggwnetcuyrtable_arg: $rggwnetcuyrtable_arg \n";
if (strlen($rggwnetcuyrtable_arg=trim($rggwnetcuyrtable_arg))) {
	$rggwnetcuyrtable = $rggwnetcuyrtable_arg;
}
if (strlen($rggwnetcuyrtable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rggwnetcuyrtable: $rggwnetcuyrtable \n";
	$options[RGGWNETCUYRTABLE] = $rggwnetcuyrtable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rggwnetcuyrtable exiting \n";
	if ($debugging) echo "missing rggwnetcuyrtable exiting \n";
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
$query = "SELECT nyear,netgwcutotal,gwrechargetotal";
$query .= " FROM $rggwnetcuyrtable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " ORDER BY nyear ASC";
$results = pg_query($pgconnection, $query);
while ($row = pg_fetch_row($results)) {
	$excitation_array[$row[0]] = $row[1]-$row[2];
}
//print_r($excitation_array);

//range definitions and linear scaling lines for each range
//$linex = 5.0;
//$liney = 100.0;
//$slope = 10.0;
$xrange_ndx_array = array();
$xrange_array = array();
$linex_array = array();
$liney_array = array();
$lineslope_array = array();
$pgtable = "rg_response_functions";
$query = "SELECT xrange_ndx,xrange_min,xrange_max,line_xval,line_yval,line_slope FROM $rgrespfntable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " AND nreach=$rgstreamreach";
$query .= " AND nrspfn=$rgrespfnversion";
$query .= " ORDER BY xrange_ndx ASC";
$results = pg_query($pgconnection, $query);
$xrange_ndx_count=0;
while ($row = pg_fetch_row($results)) {
	++$xrange_ndx_count;
	$xrange_ndx_array[$xrange_ndx_count] = $row[0];
	$xrange_array[$xrange_ndx_count] = array($row[1],$row[2]);
	$linex_array[$xrange_ndx_count] = $row[3];
	$liney_array[$xrange_ndx_count] = $row[4];
	// note that the rgdss slope has been normalized,
	//   so cpnvert it back to a standard slope
	$lineslope_array[$xrange_ndx_count] = $row[4]*$row[5];
}


// response functions for each range using range definitions from above
//$response_array=array(1=>5.0,2=>10.0,3=>15.0,4=>30.0,5=>25.0,6=>15.0);
$response_arrays=array();
for ($ndx = 1; $ndx < $xrange_ndx_count+1; $ndx++) {
	$response_array=array();
	$rgxrange_ndx = $xrange_ndx_array[$ndx];
	$query = "SELECT timestep,rspfnvalue FROM $rgrespfndatatable";
	$query .= " WHERE model_version=$rgmodelversion";
	$query .= " AND nzone=$rgresponsezone";
	$query .= " AND nreach=$rgstreamreach";
	$query .= " AND nrspfn=$rgrespfnversion";
	$query .= " AND xrange_ndx=$rgxrange_ndx";
	$query .= " ORDER BY timestep ASC"; 
	$results = pg_query($pgconnection, $query);
	while ($row = pg_fetch_row($results)) {
		$response_array[$row[0]] = $row[1];
	}
	$response_arrays[$ndx]=$response_array;
}
//print_r($response_arrays);

// subtimestep
$subtimestepcount=$options[RGSUBTIMESTEPCOUNT];

//print_r($linex_array);
//print_r($liney_array);
//print_r($lineslope_array);
//print_r($xrange_array);
$results = hybrid_convolution_linear_scaling_multiple_ranges(
				$excitation_array,
				$response_arrays,
				$subtimestepcount,
				$linex_array,
				$liney_array,
				$lineslope_array,
				$xrange_array);
print_r($results);
?>