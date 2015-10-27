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
define("PGHOST","pghost");
define("PGDB","pgdb");
define("PGPORT","pgport");

define("RGMODELVERSION","rgmodelversion");
define("RGRESPONSEZONE","rgresponsezone");
define("RGRESPONSESUBZONE","rgresponsesubzone");
define("RGSTREAMDEPLETIONSCENARIO","rgstreamdepletionscenario");
define("RGCREDITMNVERSION","rgcreditmnversion");
define("RGSUBTIMESTEPCOUNT","rgsubtimestepcount");

define("RGSTREAMDEPLETIONSCENARIOTABLE","rgstreamdepletionscenariotable");
define("RGSTREAMDEPLETIONDATATABLE","rgstreamdepletiondatatable");
define("RGCREDITMNTABLE","rgcreditmntable");
define("RGRESPFNTABLE","rgrespfntable");
define("RGRESPFNDATATABLE","rgrespfndatatable");
define("RGHYBRIDTABLE","rghybridtable");
define("RGHYBRIDDATATABLE","rghybriddatatable");

/*
 * argument defaults
 *   these are OK to modify, as long as the values are valid
 * much like pg2gviz
 * in other words, defaults get used unless over-ridden by command line args
 * the difference is they all get stuffed into an options array, makes for much cleaner code
 */
$options[DEBUGGING]=true;
$options[LOGGING]=true;

$options[PGUSER]="drupal";
$options[PGPASSWORD]="drupal_psql";
$options[PGDB]="d7dev";
$options[PGHOST]="localhost";
$options[PGPORT]=5432;
/*
 * the following are independent of whether zone or subzone calculations are being done
 * (set by the value of RGRESPONSESUBZONE)
 */
$options[RGMODELVERSION]=6; // 4 is 6P97, 5 is 6P98, 6 is 6P98final
$options[RGRESPONSEZONE]=1; // 1 is SD1 confined gw, 2 is SD2 alluvial gw, 3 is Conejos, etc.
$options[RGSTREAMDEPLETIONSCENARIO]=2; // 1 gw model data & time period, 2 is ARP 2015 data & time period, 3 is ARP 2016 data & time period, etc.
// note that the pumping years and affected stream reaches (as well as other data) are defined by the records associated with the previous scenario value
$options[RGSUBTIMESTEPCOUNT]=12; // 12 would be the usual, years to months
$options[RGRESPFNTABLE]="rg_response_functions_linear";
$options[RGRESPFNDATATABLE]="rg_response_functions_linear_data";
$options[RGHYBRIDTABLE]="rg_response_functions_hybrid";
$options[RGHYBRIDDATATABLE]="rg_response_functions_hybrid_data";
$options[RGCREDITMNTABLE]="rg_stream_depletion_credit_data";
$options[RGRESPONSESUBZONE]=0; // 0, zone calculations only, 1 is RGCWUA, 2 is ???
/*
 * the following should change based on the value of RGRESPONSESUBZONE
 * because the tables have a different structure!
 */
if (RGRESPONSESUBZONE) {
	$options[RGSTREAMDEPLETIONSCENARIOTABLE]="rg_subzone_stream_depletion_input_data_annual"; 
	$options[RGSTREAMDEPLETIONDATATABLE]="rg_subzone_stream_depletion_output_data";
} else {
	$options[RGSTREAMDEPLETIONSCENARIOTABLE]="rg_zone_stream_depletion_input_data_annual"; 
	$options[RGSTREAMDEPLETIONDATATABLE]="rg_zone_stream_depletion_output_data";
}
/*
 * this should be dependent on the value of RGSTREAMDEPLETIONSCENARIO, but isn't yet - fix it!
 */
$options[RGCREDITMNVERSION]=1;

/*
 * handle the args right here in the wrapper
 * first get the ones necessary to read the stream depletions scenario table
 */

// get the debugging arg
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
// get the logging arg
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
// get the pguser arg
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
// get the pgpassword arg
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
// get the pgdb arg
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
// get the pghost arg
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
// get the pgport arg
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
// get the rg stream depletion scenario arg
if (array_key_exists(RGSTREAMDEPLETIONSCENARIO,$options)) {
	$rgstreamdepletionscenario = $options[RGSTREAMDEPLETIONSCENARIO];
} else {
	// we can not set a default for this
	$rgstreamdepletionscenario = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgstreamdepletionscenario default: $rgstreamdepletionscenario \n";
$rgstreamdepletionscenario_arg = getargs (RGSTREAMDEPLETIONSCENARIO,$rgstreamdepletionscenario);
if ($debugging) echo "rgstreamdepletionscenario_arg: $rgstreamdepletionscenario_arg \n";
if (strlen($rgstreamdepletionscenario_arg=trim($rgstreamdepletionscenario_arg))) {
	$rgstreamdepletionscenario = intval($rgstreamdepletionscenario_arg);
}
if ($rgstreamdepletionscenario > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgstreamdepletionscenario: $rgstreamdepletionscenario \n";
	$options[RGSTREAMDEPLETIONSCENARIO] = $rgstreamdepletionscenario;
} else {
	// can not proceed without this
	if ($logging) echo "invalid rgstreamdepletionscenario: $rgstreamdepletionscenario exiting \n";
	if ($debugging) echo "invalid rgstreamdepletionscenario: $rgstreamdepletionscenario exiting \n";
	return;
}
// get the stream depletion scenario table arg
if (array_key_exists(RGSTREAMDEPLETIONSCENARIOTABLE,$options)) {
	$rgstreamdepletionscenariotable = trim($options[RGSTREAMDEPLETIONSCENARIOTABLE]);
} else {
	// we can NOT set a default for this
	$rgstreamdepletionscenariotable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgstreamdepletionscenariotable default: $rgstreamdepletionscenariotable \n";
$rgstreamdepletionscenariotable_arg = getargs (RGSTREAMDEPLETIONSCENARIOTABLE,$rgstreamdepletionscenariotable);
if ($debugging) echo "rgstreamdepletionscenariotable_arg: $rgstreamdepletionscenariotable_arg \n";
if (strlen($rgstreamdepletionscenariotable_arg=trim($rgstreamdepletionscenariotable_arg))) {
	$rgstreamdepletionscenariotable = $rgstreamdepletionscenariotable_arg;
}
if (strlen($rgstreamdepletionscenariotable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgstreamdepletionscenariotable: $rgstreamdepletionscenariotable \n";
	$options[RGSTREAMDEPLETIONSCENARIOTABLE] = $rgstreamdepletionscenariotable;
} else {
	// can not proceed without this
	if ($logging) echo "missing $rgstreamdepletionscenariotable exiting \n";
	if ($debugging) echo "missing $rgstreamdepletionscenariotable exiting \n";
	return;
}
// get the stream depletion data table arg
if (array_key_exists(RGSTREAMDEPLETIONDATATABLE,$options)) {
	$rgstreamdepletiondatatable = trim($options[RGSTREAMDEPLETIONDATATABLE]);
} else {
	// we can NOT set a default for this
	$rgstreamdepletiondatatable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgstreamdepletiondatatable default: $rgstreamdepletiondatatable \n";
$rgstreamdepletiondatatable_arg = getargs (RGSTREAMDEPLETIONDATATABLE,$rgstreamdepletiondatatable);
if ($debugging) echo "rgstreamdepletiondatatable_arg: $rgstreamdepletiondatatable_arg \n";
if (strlen($rgstreamdepletiondatatable_arg=trim($rgstreamdepletiondatatable_arg))) {
	$rgstreamdepletiondatatable = $rgstreamdepletiondatatable_arg;
}
if (strlen($rgstreamdepletiondatatable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgstreamdepletiondatatable: $rgstreamdepletiondatatable \n";
	$options[RGSTREAMDEPLETIONDATATABLE] = $rgstreamdepletiondatatable;
} else {
	// can not proceed without this
	if ($logging) echo "missing $rgstreamdepletiondatatable exiting \n";
	if ($debugging) echo "missing $rgstreamdepletiondatatable exiting \n";
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

/*
 * now get the rest of the args
 * 
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
// get the rg stream depletion credit monthly data version arg
if (array_key_exists(RGCREDITMNVERSION,$options)) {
	$rgcreditmnversion = $options[RGCREDITMNVERSION];
} else {
	// we can not set a default for this
	$rgcreditmnversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgcreditmnversion default: $rgcreditmnversion \n";
$rgcreditmnversion_arg = getargs (RGCREDITMNVERSION,$rgcreditmnversion);
if ($debugging) echo "rgcreditmnversion_arg: $rgcreditmnversion_arg \n";
if (strlen($rgcreditmnversion_arg=trim($rgcreditmnversion_arg))) {
	$rgcreditmnversion = intval($rgcreditmnversion_arg);
}
if ($rgcreditmnversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgcreditmnversion: $rgcreditmnversion \n";
	$options[RGCREDITMNVERSION] = $rgcreditmnversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgcreditmnversion: $rgcreditmnversion exiting \n";
	if ($debugging) echo "invalid rgcreditmnversion: $rgcreditmnversion exiting \n";
	return;
}
// get the rg response zone arg
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
// get the rg response zone subzonearg
if (array_key_exists(RGRESPONSESUBZONE,$options)) {
	$rgresponsesubzone = $options[RGRESPONSESUBZONE];
} else {
	// we can not set a default for this
	$rgresponsesubzone = 0; // set it to a default value, 0 - zone calculations only
}
if ($debugging) echo "rgresponsesubzone default: $rgresponsesubzone \n";
$rgresponsesubzone_arg = getargs (RGRESPONSESUBZONE,$rgresponsesubzone);
if ($debugging) echo "rgresponsesubzone_arg: $rgresponsesubzone_arg \n";
if (strlen($rgresponsesubzone_arg=trim($rgresponsesubzone_arg))) {
	$rgresponsesubzone = intval($rgresponsesubzone_arg);
}
if ($rgresponsesubzone >= 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgresponsesubzone: $rgresponsesubzone \n";
	$options[RGRESPONSESUBZONE] = $rgresponsesubzone;
} else {
	// can not proceed without this
	if ($logging) echo "invalid rgresponsesubzone: $rgresponsesubzone exiting \n";
	if ($debugging) echo "invalid rgresponsesubzone: $rgresponsesubzone exiting \n";
	return;
}
// get the rgrespfntable arg
if (array_key_exists(RGRESPFNTABLE,$options)) {
	$rgrespfntable = trim($options[RGRESPFNTABLE]);
} else {
	// we can NOT set a default for this
	$rgrespfntable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgrespfntable default: $rgrespfntable \n";
$rgrespfntable_arg = getargs (RGRESPFNTABLE,$rgrespfntable);
if ($debugging) echo "rgrespfntable_arg: $rgrespfntable_arg \n";
if (strlen($rgrespfntable_arg=trim($rgrespfntable_arg))) {
	$rgrespfntable = $rgrespfntable_arg;
}
if (strlen($rgrespfntable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgrespfntable: $rgrespfntable \n";
	$options[RGRESPFNTABLE] = $rgrespfntable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgrespfntable exiting \n";
	if ($debugging) echo "missing rgrespfntable exiting \n";
	return;
}
// get the rgrespfntable arg
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
// get the rgcreditmntable arg
if (array_key_exists(RGCREDITMNTABLE,$options)) {
	$rgcreditmntable = trim($options[RGCREDITMNTABLE]);
} else {
	// we can NOT set a default for this
	$rgcreditmntable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgcreditmntable default: $rgcreditmntable \n";
$rgcreditmntable_arg = getargs (RGCREDITMNTABLE,$rgcreditmntable);
if ($debugging) echo "rgcreditmntable_arg: $rgcreditmntable_arg \n";
if (strlen($rgcreditmntable_arg=trim($rgcreditmntable_arg))) {
	$rgcreditmntable = $rgcreditmntable_arg;
}
if (strlen($rgcreditmntable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgcreditmntable: $rgcreditmntable \n";
	$options[RGCREDITMNTABLE] = $rgcreditmntable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgcreditmntable exiting \n";
	if ($debugging) echo "missing rgcreditmntable exiting \n";
	return;
}

/*
 * set up the excitation array
 * get the all the necessary data from the zone's stream depletion input data records for this "scenario"
 * these will include annual netgwcu values to be calculated from the annual gwcu and recharge values
 * (keeping them separate in the data is important for subzone calculations)
 * the year range and impacted stream reaches and most other data can be determined from
 *   the zone's stream depletion input data records for this "scenario"
 * each record represents one response function application
 *   in other words, each record will be used to create a time series of stream depletions (usually 20 years long)
 *   these stream depletions will be saved separately in the pg database to be aggregated later via queries
 *   to create exactly the reposne that the user desires (without having to reconvolute the same data over and over)   
 */
//$excitation_array=array(2001=>5.0,2002=>10.0,2003=>0.0,2004=>15.0);

// clear out the previous data for this stream depletion scenario
$delete_array=array();
$delete_array['model_version']=$rgmodelversion;
$delete_array['nzone']=$rgresponsezone;
if($rgresponsesubzone) {
	$delete_array['nsubzone']=$rgresponsezone;
}
$delete_array['nscenario']=$rgstreamdepletionscenario;
pg_delete($pgconnection,$rgstreamdepletiondatatable,$delete_array);
// subtimestep
$subtimestepcount=$options[RGSUBTIMESTEPCOUNT];
// get the str depl scenario records
if($rgresponsesubzone) {
	$query = "SELECT model_version, nzone, nsubzone, nreach, nscenario, nyear,";
	$query .= " subzone_gwcu_af, subzone_recharge_af, zone_gwcu_af, zone_recharge_af, streamflow_aprsep_af,";
	$query .= " grouping_type, streamflow_avg_af, resp_fn_type, resp_fn_ndx";
	$query .= " FROM $rgstreamdepletionscenariotable";
	$query .= " WHERE model_version=$rgmodelversion";
	$query .= " AND nzone=$rgresponsezone";
	$query .= " AND nsubzone=$rgresponsesubzone";
	$query .= " AND nscenario=$rgstreamdepletionscenario";
	$query .= " ORDER BY nyear, nreach";
} else {
	$query = "SELECT model_version, nzone, nreach, nscenario, nyear,";
	$query .= " zone_gwcu_af, zone_recharge_af, streamflow_aprsep_af,";
	$query .= " grouping_type, streamflow_avg_af, resp_fn_type, resp_fn_ndx";
	$query .= " FROM $rgstreamdepletionscenariotable";
	$query .= " WHERE model_version=$rgmodelversion";
	$query .= " AND nzone=$rgresponsezone";
	$query .= " AND nscenario=$rgstreamdepletionscenario";
	$query .= " ORDER BY nyear, nreach";
}
$results = pg_query($pgconnection, $query);
$recordcount=0;
$model_version=array();
$nzone=array();
$nreach=array();
$nscenario=array();
$nyear=array();
if($rgresponsesubzone) {
	$subzone_gwcu_af=array();
	$subzone_recharge_af=array();
	$subzone_netgwcu_af=array();
}
$zone_gwcu_af=array();
$zone_recharge_af=array();
$zone_netgwcu_af=array();
$zone_grpval=array();
$streamflow_aprsep_af=array();
$grouping_type=array();
$streamflow_avg_af=array();
$resp_fn_type=array();
$resp_fn_ndx=array();
// loop through the records and save the values into arrays
while ($row = pg_fetch_row($results)) {
	$model_version[$recordcount]=$row[0];
	$nzone[$recordcount]=$row[1];
	if($rgresponsesubzone) {
		$nsubzone[$recordcount]=$row[2];
		$nreach[$recordcount]=$row[3];
		$nscenario[$recordcount]=$row[4];
		$nyear[$recordcount]=$row[5];
		$subzone_gwcu_af[$recordcount]=$row[6];
		$subzone_recharge_af[$recordcount]=$row[7];
		$subzone_netgwcu_af[$recordcount]=$row[6]-$row[7];
		$zone_gwcu_af[$recordcount]=$row[8];
		$zone_recharge_af[$recordcount]=$row[9];
		$zone_netgwcu_af[$recordcount]=$row[8]-$row[9];
		$streamflow_aprsep_af[$recordcount]=$row[10];
		$grouping_type[$recordcount]=$row[11];
		switch ($grouping_type[$recordcount]) {
			case 1: // gwnetcu
				$zone_grpval[$recordcount]=$zone_netgwcu_af[$recordcount];
				break;
			case 2: // stream flow
				$zone_grpval[$recordcount]=$streamflow_aprsep_af[$recordcount];
				break;
			default:
				$zone_grpval[$recordcount]=$zone_netgwcu_af[$recordcount];
		}
		$streamflow_avg_af[$recordcount]=$row[12];
		$resp_fn_type[$recordcount]=$row[13];
		$resp_fn_ndx[$recordcount]=$row[14];
	} else {
		$nreach[$recordcount]=$row[2];
		$nscenario[$recordcount]=$row[3];
		$nyear[$recordcount]=$row[4];
		$zone_gwcu_af[$recordcount]=$row[5];
		$zone_recharge_af[$recordcount]=$row[6];
		$zone_netgwcu_af[$recordcount]=$row[5]-$row[6];
		$streamflow_aprsep_af[$recordcount]=$row[7];
		$grouping_type[$recordcount]=$row[8];
		switch ($grouping_type[$recordcount]) {
			case 1: // gwnetcu
				$zone_grpval[$recordcount]=$zone_netgwcu_af[$recordcount];
				break;
			case 2: // stream flow
				$zone_grpval[$recordcount]=$streamflow_aprsep_af[$recordcount];
				break;
			default:
				$zone_grpval[$recordcount]=$zone_netgwcu_af[$recordcount];
		}
		$streamflow_avg_af[$recordcount]=$row[9];
		$resp_fn_type[$recordcount]=$row[10];
		$resp_fn_ndx[$recordcount]=$row[11];
	}
	++$recordcount;
	//$netgwcu_array[$row[0]] = $row[1];
}
// loop through the arrays and create a stream depletion time series for each using the response functions
// even though the convolution routine can handle a time series of excitations,
//   instead run each year and stream reach separately so they can be saved separately
for ($i = 0; $i < $recordcount; $i++) {
	//net gw cu
	if ($debugging) {
		echo "i $i year $nyear[$i] reach $nreach[$i] \n";
	}
	if($rgresponsesubzone) {
		$subzone_gwcu_array = array();
		$subzone_recharge_array = array();
		$subzone_gwcu_array[$nyear[$i]]=$subzone_gwcu_af[$i];
		$subzone_recharge_array[$nyear[$i]]=$subzone_recharge_af[$i];
	}
	$zone_gwcu_array = array();
	$zone_recharge_array = array();
	$zone_gwcu_array[$nyear[$i]]=$zone_gwcu_af[$i];
	$zone_recharge_array[$nyear[$i]]=$zone_recharge_af[$i];
	$zone_grpval_array = array();
	$zone_grpval_array[$nyear[$i]]=$zone_grpval[$i];
	$startyear = $nyear[$i];
	//range definitions and linear scaling lines for each range
	//$xrange_ndx_array = array();
	//$xrange_array = array();
	$group_ndx_array = array();
	$group_range_array = array();
	$linex_array = array();
	$liney_array = array();
	$lineslope_array = array();
	$rgstreamreach=$nreach[$i];
	$rgrespfnversion=$resp_fn_ndx[$i];
	$query = "SELECT group_ndx,group_min,group_max,line_xval,line_yval,line_slope FROM $rgrespfntable";
	$query .= " WHERE model_version=$rgmodelversion";
	$query .= " AND nzone=$rgresponsezone";
	$query .= " AND nreach=$rgstreamreach";
	$query .= " AND nrspfn=$rgrespfnversion";
	$query .= " ORDER BY group_ndx ASC";
	$results = pg_query($pgconnection, $query);
	$group_ndx_count=0;
	while ($row = pg_fetch_row($results)) {
		++$group_ndx_count;
		$group_ndx_array[$group_ndx_count] = $row[0];
		$group_range_array[$group_ndx_count] = array($row[1],$row[2]);
		$linex_array[$group_ndx_count] = $row[3];
		$liney_array[$group_ndx_count] = $row[4];
		// note that the rgdss slope has been normalized,
		//   so cpnvert it back to a standard slope
		$lineslope_array[$group_ndx_count] = $row[4]*$row[5];
	}	
	// response functions for each range using range definitions from above
	$response_arrays=array();
	for ($ndx = 1; $ndx < $group_ndx_count+1; $ndx++) {
		$response_array=array();
		$rggroup_ndx = $group_ndx_array[$ndx];
		$query = "SELECT timestep,rspfnvalue FROM $rgrespfndatatable";
		$query .= " WHERE model_version=$rgmodelversion";
		$query .= " AND nzone=$rgresponsezone";
		$query .= " AND nreach=$rgstreamreach";
		$query .= " AND nrspfn=$rgrespfnversion";
		$query .= " AND group_ndx=$rggroup_ndx";
		$query .= " ORDER BY timestep ASC"; 
		$results = pg_query($pgconnection, $query);
		while ($row = pg_fetch_row($results)) {
			$response_array[$row[0]] = $row[1];
		}
		$response_arrays[$ndx]=$response_array;
	}
	// run the convolution and create the stream depletion time series
	if($rgresponsesubzone) {
		$results = hybrid_convolution_linear_scaling_multiple_ranges_subzone(
				$zone_grpval_array,
				$zone_gwcu_array,
				$zone_recharge_array,
				$subzone_gwcu_array,
				$subzone_recharge_array,
				$response_arrays,
				$subtimestepcount,
				$linex_array,
				$liney_array,
				$lineslope_array,
				$group_range_array);
	} else {
		$results = hybrid_convolution_linear_scaling_multiple_ranges(
				$zone_grpval_array,
				$zone_gwcu_array,
				$zone_recharge_array,
				$response_arrays,
				$subtimestepcount,
				$linex_array,
				$liney_array,
				$lineslope_array,
				$group_range_array);
	}
	
	// save the stream depletion time series back to a pg table
	if(count($results)) {
		foreach ($results as $ndx=>$value) {
			$insert_array=array();
			$insert_array['model_version']=$rgmodelversion;
			$insert_array['nzone']=$rgresponsezone;
			if($rgresponsesubzone) {
				$insert_array['nsubzone']=$rgresponsesubzone;
			}
			$insert_array['nscenario']=$rgstreamdepletionscenario;
			$insert_array['nreach']=$rgstreamreach;
			$insert_array['nyear']=$startyear;
			$absolutetimestep = $ndx+($startyear-1900)*$subtimestepcount;
			$insert_array['ntimestep'] = $absolutetimestep;
			//if(array_key_exists($absolutetimestep,$credit_array)) {
			//	$value += $credit_array[$absolutetimestep];
			//}
			$insert_array['depletion_af'] = $value;
			pg_insert($pgconnection,$rgstreamdepletiondatatable,$insert_array);
		}
	}
}


?>
