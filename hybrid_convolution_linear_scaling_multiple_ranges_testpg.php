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

define("RGSTREAMDEPLETIONSCENARIO","rgstreamdepletionscenario");
define("RGSTREAMDEPLETIONSCENARIOTABLE","rgstreamdepletionscenariotable");

define("RGMODELVERSION","rgmodelversion");
define("RGRESPONSEZONE","rgresponsezone");
define("RGSTREAMREACH","rgstreamreach");
define("RGRESPFNVERSION","rgrespfnversion");

define("RGPERIODYRVERSION","rgperiodyrversion");
define("RGNETGWCUYRVERSION","rgnetgwcuyrversion");
define("RGPUMPINGYRVERSION","rgpumpingyrversion");
define("RGEFFICIENCYYRVERSION","rgefficiencyyrversion");
define("RGRECHARGEYRVERSION","rgrechargeyrversion");

define("RGSUBTIMESTEPCOUNT","rgsubtimestepcount");

define("RGRESPFNFTABLE","rgrespfntable");
define("RGRESPFNDATATABLE","rgrespfndatatable");
define("RGPERIODYRTABLE","rgperiodyrtable");
define("RGNETGWCUYRTABLE","rgnetgwcuyrtable"); //used to be RGGWNETCUYRTABLE
define("RGPUMPINGYRTABLE","rgpumpingyrtable");
define("RGEFFICIENCYYRTABLE","rgefficiencyyrtable");
define("RGRECHARGEYRTABLE","rgrechargeyrtable");


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

$options[RGSTREAMDEPLETIONSCENARIO]=1; // 1 is 6P98 official data, 1970-2015
$options[RGSTREAMDEPLETIONSCENARIOTABLE]="rg_stream_depletion_scenarios";

/*
 * setting these no longer has any effect these,
 * since they get over-written with the values in the stream depletion scenario table
 * but if somehow that fails (unlikely to be allowed), it would fall back to these as defaults
 */
$options[RGMODELVERSION]=5; // 4 is 6P97, 5 is 6P98
$options[RGRESPONSEZONE]=1; // 1 is SD1 confined gw, 2 is SD2 alluvial gw, 3 is Conejos, etc.
$options[RGSTREAMREACH]=1; // 1 is RG1 (del norte to excelsior), 2 is RG2 (excelsior to chicago), etc.
$options[RGRESPFNVERSION]=1; // 1 would be the usual, the officially released one from DWR, unless there happen to multiple versions in the database
$options[RGPERIODYRVERSION]=1; // 0 - all the years in the input data, 1 - modeled period (for example 1970-2010 for 6P98), 2 - 1970-2015
$options[RGNETGWCUYRVERSION]=1; // 1 - the official netgwcu annual values for the model version (used in the response function calibration)
$options[RGPUMPINGYRVERSION]=1; // 1 - the official annual well pumping total used in the ARP reports for this zone and for this model version
$options[RGEFFICIENCYYRVERSION]=1; // 1 - the official annual well pumping efficiency values used in the ARP reports for this zone and for this model version
$options[RGRECHARGEYRVERSION]=1; // 1 - the official annual decreed aquifer recharge values used in the ARP reports for this zone and for this model version
//$subtimestepcount
$options[RGSUBTIMESTEPCOUNT]=12; // 12 would be the usual, years to months

$options[RGRESPFNFTABLE]="rg_response_functions";
$options[RGRESPFNDATATABLE]="rg_response_function_data";
$options[RGPERIODYRTABLE]="rg_response_zone_period_annual_scenarios";
$options[RGNETGWCUYRTABLE]="rg_response_zone_netgwcu_annual_data"; //used to be RGGWNETCUYRTABLE
$options[RGPUMPINGYRTABLE]="rg_response_zone_pumping_annual_data";
$options[RGEFFICIENCYYRTABLE]="rg_response_zone_pumping_efficiency_annual_data";
$options[RGRECHARGEYRTABLE]="rg_response_zone_recharge_annual_data";

/*
 * handle the args right here in the wrapper
 * first get the ones necessary to read the stream depletions scenario table
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
 * get the rg stream depletion scenario arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
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
/*
 * get the stream depletion scenario table arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
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
 * get the stream depletion scenario parameters
 *   these essentially become the defaults,
 *     but it is unlikely anyone (except me...)
 *     will input separate parameters to over-ride them
 *     because it will still get written to the output table
 *     using the $rgstreamdepletionscenario value
 */
$query = "SELECT ";
$query .= "model_version,";
$query .= "nzone,";
$query .= "nperiodyrscenario,";
$query .= "nnetgwcuyrscenario,";
$query .= "npumpingyrscenario,";
$query .= "nefficiencyyrscenario,";
$query .= "nrechargeyrscenario,";
$query .= "nreach,";
$query .= "nrspfn ";
$query .= " FROM $rgstreamdepletionscenariotable";
$query .= " WHERE ndx=$rgstreamdepletionscenario";
$results = pg_query($pgconnection, $query);
while ($row = pg_fetch_row($results)) {
    if($debugging) print_r($row);
	$options[RGMODELVERSION] = $row[0]; 
	$options[RGRESPONSEZONE] = $row[1]; 
	$options[RGPERIODYRVERSION] = $row[2]; 
	$options[RGNETGWCUYRVERSION] = $row[3]; 
	$options[RGPUMPINGYRVERSION] = $row[4]; 
	$options[RGEFFICIENCYYRVERSION] = $row[5]; 
	$options[RGRECHARGEYRVERSION] = $row[6]; 
	$options[RGSTREAMREACH] = $row[7]; 
	$options[RGRESPFNVERSION] = $row[8]; 
}
if($debugging) print_r($options);
/*
 * now get the rest of the args
 */

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
 * get the rg period data (years) version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGPERIODYRVERSION,$options)) {
	$rgperiodyrversion = $options[RGPERIODYRVERSION];
} else {
	// we can not set a default for this
	$rgperiodyrversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgperiodyrversion default: $rgperiodyrversion \n";
$rgperiodyrversion_arg = getargs (RGPERIODYRVERSION,$rgperiodyrversion);
if ($debugging) echo "rgperiodyrversion_arg: $rgperiodyrversion_arg \n";
if (strlen($rgperiodyrversion_arg=trim($rgperiodyrversion_arg))) {
	$rgperiodyrversion = intval($rgperiodyrversion_arg);
}
if ($rgperiodyrversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgperiodyrversion: $rgperiodyrversion \n";
	$options[RGPERIODYRVERSION] = $rgperiodyrversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgperiodyrversion: $rgperiodyrversion exiting \n";
	if ($debugging) echo "invalid rgperiodyrversion: $rgperiodyrversion exiting \n";
	return;
}
/*
 * get the rg netgwcu annual data version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGNETGWCUYRVERSION,$options)) {
	$rgnetgwcuyrversion = $options[RGNETGWCUYRVERSION];
} else {
	// we can not set a default for this
	$rgnetgwcuyrversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgnetgwcuyrversion default: $rgnetgwcuyrversion \n";
$rgnetgwcuyrversion_arg = getargs (RGNETGWCUYRVERSION,$rgnetgwcuyrversion);
if ($debugging) echo "rgnetgwcuyrversion_arg: $rgnetgwcuyrversion_arg \n";
if (strlen($rgnetgwcuyrversion_arg=trim($rgnetgwcuyrversion_arg))) {
	$rgnetgwcuyrversion = intval($rgnetgwcuyrversion_arg);
}
if ($rgnetgwcuyrversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgnetgwcuyrversion: $rgnetgwcuyrversion \n";
	$options[RGNETGWCUYRVERSION] = $rgnetgwcuyrversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgnetgwcuyrversion: $rgnetgwcuyrversion exiting \n";
	if ($debugging) echo "invalid rgnetgwcuyrversion: $rgnetgwcuyrversion exiting \n";
	return;
}
/*
 * get the rg pumping annual data version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGPUMPINGYRVERSION,$options)) {
	$rgpumpingyrversion = $options[RGPUMPINGYRVERSION];
} else {
	// we can not set a default for this
	$rgpumpingyrversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgpumpingyrversion default: $rgpumpingyrversion \n";
$rgpumpingyrversion_arg = getargs (RGPUMPINGYRVERSION,$rgpumpingyrversion);
if ($debugging) echo "rgpumpingyrversion_arg: $rgpumpingyrversion_arg \n";
if (strlen($rgpumpingyrversion_arg=trim($rgpumpingyrversion_arg))) {
	$rgpumpingyrversion = intval($rgpumpingyrversion_arg);
}
if ($rgpumpingyrversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgpumpingyrversion: $rgpumpingyrversion \n";
	$options[RGPUMPINGYRVERSION] = $rgpumpingyrversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgpumpingyrversion: $rgpumpingyrversion exiting \n";
	if ($debugging) echo "invalid rgpumpingyrversion: $rgpumpingyrversion exiting \n";
	return;
}
/*
 * get the rg pumping efficiency annual data version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGEFFICIENCYYRVERSION,$options)) {
	$rgefficiencyyrversion = $options[RGEFFICIENCYYRVERSION];
} else {
	// we can not set a default for this
	$rgefficiencyyrversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgefficiencyyrversion default: $rgefficiencyyrversion \n";
$rgefficiencyyrversion_arg = getargs (RGEFFICIENCYYRVERSION,$rgefficiencyyrversion);
if ($debugging) echo "rgefficiencyyrversion_arg: $rgefficiencyyrversion_arg \n";
if (strlen($rgefficiencyyrversion_arg=trim($rgefficiencyyrversion_arg))) {
	$rgefficiencyyrversion = intval($rgefficiencyyrversion_arg);
}
if ($rgefficiencyyrversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgefficiencyyrversion: $rgefficiencyyrversion \n";
	$options[RGEFFICIENCYYRVERSION] = $rgefficiencyyrversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgefficiencyyrversion: $rgefficiencyyrversion exiting \n";
	if ($debugging) echo "invalid rgefficiencyyrversion: $rgefficiencyyrversion exiting \n";
	return;
}
/*
 * get the rg recharge annual data version arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRECHARGEYRVERSION,$options)) {
	$rgrechargeyrversion = $options[RGRECHARGEYRVERSION];
} else {
	// we can not set a default for this
	$rgrechargeyrversion = 0; // set it to an invalid value and check later
}
if ($debugging) echo "rgrechargeyrversion default: $rgrechargeyrversion \n";
$rgrechargeyrversion_arg = getargs (RGRECHARGEYRVERSION,$rgrechargeyrversion);
if ($debugging) echo "rgrechargeyrversion_arg: $rgrechargeyrversion_arg \n";
if (strlen($rgrechargeyrversion_arg=trim($rgrechargeyrversion_arg))) {
	$rgrechargeyrversion = intval($rgrechargeyrversion_arg);
}
if ($rgrechargeyrversion > 0) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgrechargeyrversion: $rgrechargeyrversion \n";
	$options[RGRECHARGEYRVERSION] = $rgrechargeyrversion;
} else {
	// can not proceed without this
    if ($logging) echo "invalid rgrechargeyrversion: $rgrechargeyrversion exiting \n";
	if ($debugging) echo "invalid rgrechargeyrversion: $rgrechargeyrversion exiting \n";
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
 * get the rgnetgwcuyrtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGNETGWCUYRTABLE,$options)) {
	$rgnetgwcuyrtable = trim($options[RGNETGWCUYRTABLE]);
} else {
	// we can NOT set a default for this
	$rgnetgwcuyrtable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgnetgwcuyrtable default: $rgnetgwcuyrtable \n";
$rgnetgwcuyrtable_arg = getargs (RGNETGWCUYRTABLE,$rgnetgwcuyrtable);
if ($debugging) echo "rgnetgwcuyrtable_arg: $rgnetgwcuyrtable_arg \n";
if (strlen($rgnetgwcuyrtable_arg=trim($rgnetgwcuyrtable_arg))) {
	$rgnetgwcuyrtable = $rgnetgwcuyrtable_arg;
}
if (strlen($rgnetgwcuyrtable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgnetgwcuyrtable: $rgnetgwcuyrtable \n";
	$options[RGNETGWCUYRTABLE] = $rgnetgwcuyrtable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgnetgwcuyrtable exiting \n";
	if ($debugging) echo "missing rgnetgwcuyrtable exiting \n";
	return;
}
/*
 * get the rgperiodyrtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGPERIODYRTABLE,$options)) {
	$rgperiodyrtable = trim($options[RGPERIODYRTABLE]);
} else {
	// we can NOT set a default for this
	$rgperiodyrtable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgperiodyrtable default: $rgperiodyrtable \n";
$rgperiodyrtable_arg = getargs (RGPERIODYRTABLE,$rgperiodyrtable);
if ($debugging) echo "rgperiodyrtable_arg: $rgperiodyrtable_arg \n";
if (strlen($rgperiodyrtable_arg=trim($rgperiodyrtable_arg))) {
	$rgperiodyrtable = $rgperiodyrtable_arg;
}
if (strlen($rgperiodyrtable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgperiodyrtable: $rgperiodyrtable \n";
	$options[RGPERIODYRTABLE] = $rgperiodyrtable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgperiodyrtable exiting \n";
	if ($debugging) echo "missing rgperiodyrtable exiting \n";
	return;
}
/*
 * get the rgpumpingyrtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGPUMPINGYRTABLE,$options)) {
	$rgpumpingyrtable = trim($options[RGPUMPINGYRTABLE]);
} else {
	// we can NOT set a default for this
	$rgpumpingyrtable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgpumpingyrtable default: $rgpumpingyrtable \n";
$rgpumpingyrtable_arg = getargs (RGPUMPINGYRTABLE,$rgpumpingyrtable);
if ($debugging) echo "rgpumpingyrtable_arg: $rgpumpingyrtable_arg \n";
if (strlen($rgpumpingyrtable_arg=trim($rgpumpingyrtable_arg))) {
	$rgpumpingyrtable = $rgpumpingyrtable_arg;
}
if (strlen($rgpumpingyrtable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgpumpingyrtable: $rgpumpingyrtable \n";
	$options[RGPUMPINGYRTABLE] = $rgpumpingyrtable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgpumpingyrtable exiting \n";
	if ($debugging) echo "missing rgpumpingyrtable exiting \n";
	return;
}
/*
 * get the rgefficiencyyrtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGEFFICIENCYYRTABLE,$options)) {
	$rgefficiencyyrtable = trim($options[RGEFFICIENCYYRTABLE]);
} else {
	// we can NOT set a default for this
	$rgefficiencyyrtable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgefficiencyyrtable default: $rgefficiencyyrtable \n";
$rgefficiencyyrtable_arg = getargs (RGEFFICIENCYYRTABLE,$rgefficiencyyrtable);
if ($debugging) echo "rgefficiencyyrtable_arg: $rgefficiencyyrtable_arg \n";
if (strlen($rgefficiencyyrtable_arg=trim($rgefficiencyyrtable_arg))) {
	$rgefficiencyyrtable = $rgefficiencyyrtable_arg;
}
if (strlen($rgefficiencyyrtable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgefficiencyyrtable: $rgefficiencyyrtable \n";
	$options[RGEFFICIENCYYRTABLE] = $rgefficiencyyrtable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgefficiencyyrtable exiting \n";
	if ($debugging) echo "missing rgefficiencyyrtable exiting \n";
	return;
}
/*
 * get the rgrechargeyrtable arg
 * this is required, so bail if it is not set from either the default above or the cli arg
 */
if (array_key_exists(RGRECHARGEYRTABLE,$options)) {
	$rgrechargeyrtable = trim($options[RGRECHARGEYRTABLE]);
} else {
	// we can NOT set a default for this
	$rgrechargeyrtable = ""; // set it to an invalid value and check later
}
if ($debugging) echo "rgrechargeyrtable default: $rgrechargeyrtable \n";
$rgrechargeyrtable_arg = getargs (RGRECHARGEYRTABLE,$rgrechargeyrtable);
if ($debugging) echo "rgrechargeyrtable_arg: $rgrechargeyrtable_arg \n";
if (strlen($rgrechargeyrtable_arg=trim($rgrechargeyrtable_arg))) {
	$rgrechargeyrtable = $rgrechargeyrtable_arg;
}
if (strlen($rgrechargeyrtable)) {
	// a potentially valid value, use it
	if ($debugging) echo "final rgrechargeyrtable: $rgrechargeyrtable \n";
	$options[RGRECHARGEYRTABLE] = $rgrechargeyrtable;
} else {
	// can not proceed without this
	if ($logging) echo "missing rgrechargeyrtable exiting \n";
	if ($debugging) echo "missing rgrechargeyrtable exiting \n";
	return;
}

/*
 * set up the excitation array
 * first get the netgwcu values
 *   typically these are the official annual total netgwcu values for this model version for the modeling period
 *   (for example 6P98 this would be the netcu values from 1970 to 2010)
 *   these already have the efficiency and decreed recharge built into them
 * second get any additional pumping, pumping efficiency and recharge data for additional years beyond the modeling period
 *   typically these are used for years post modeling period
 *   (for example 6P98 this would be 2011 to current year)
 * add them together and then constrain to the specified calculation period
 *   note that by adding them together, this could also be used to modify netgwcu values during the modeling period
 */
//$excitation_array=array(2001=>5.0,2002=>10.0,2003=>0.0,2004=>15.0);
$query = "SELECT nyear,value";
$query .= " FROM $rgnetgwcuyrtable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " AND nscenario=$rgnetgwcuyrversion";
$query .= " ORDER BY nyear ASC";
$results = pg_query($pgconnection, $query);
while ($row = pg_fetch_row($results)) {
	$netgwcu_array[$row[0]] = $row[1];
}
//print_r($netgwcu_array);
$query = "SELECT nyear,flood,sprinkler";
$query .= " FROM $rgpumpingyrtable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " AND nscenario=$rgpumpingyrversion";
$query .= " ORDER BY nyear ASC";
$results = pg_query($pgconnection, $query);
while ($row = pg_fetch_row($results)) {
	$pumping_flood_array[$row[0]] = $row[1];
	$pumping_sprinkler_array[$row[0]] = $row[2];
}
//print_r($pumping_flood_array);
//print_r($pumping_sprinkler_array);
$query = "SELECT nyear,flood,sprinkler";
$query .= " FROM $rgefficiencyyrtable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " AND nscenario=$rgefficiencyyrversion";
$query .= " ORDER BY nyear ASC";
$results = pg_query($pgconnection, $query);
while ($row = pg_fetch_row($results)) {
	$efficiency_flood_array[$row[0]] = $row[1];
	$efficiency_sprinkler_array[$row[0]] = $row[2];
}
//print_r($efficiency_flood_array);
//print_r($efficiency_sprinkler_array);
$query = "SELECT nyear,value";
$query .= " FROM $rgrechargeyrtable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " AND nscenario=$rgrechargeyrversion";
$query .= " ORDER BY nyear ASC";
$results = pg_query($pgconnection, $query);
while ($row = pg_fetch_row($results)) {
	$recharge_array[$row[0]] = $row[1];
}
//print_r($recharge_array);
$query = "SELECT startyear, endyear";
$query .= " FROM $rgperiodyrtable";
$query .= " WHERE model_version=$rgmodelversion";
$query .= " AND nzone=$rgresponsezone";
$query .= " AND nscenario=$rgperiodyrversion";
$results = pg_query($pgconnection, $query);
$period_array = array();
while ($row = pg_fetch_row($results)) {
	$startyear = $row[0];
	$endyear = $row[1];
}
//print_r($period_array);
$excitation_array = array();
for ($year = $startyear; $year <= $endyear; $year++) {
	$excitation_array[$year]=0.0;
	if(array_key_exists($year,$netgwcu_array)) {
		$excitation_array[$year] += $netgwcu_array[$year];
	}
	if(array_key_exists($year,$pumping_flood_array)&&array_key_exists($year,$efficiency_flood_array)) {
		$excitation_array[$year] += $pumping_flood_array[$year]*$efficiency_flood_array[$year];
	}
	if(array_key_exists($year,$pumping_sprinkler_array)&&array_key_exists($year,$efficiency_sprinkler_array)) {
		$excitation_array[$year] += $pumping_sprinkler_array[$year]*$efficiency_sprinkler_array[$year];
	}
	if(array_key_exists($year,$recharge_array)) {
		$excitation_array[$year] -= $recharge_array[$year];
	}
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

if(count($results)) {
	$delete_array['nscenario']=$rgstreamdepletionscenario;
	pg_delete($pgconnection,$delete_array);
	foreach ($results as $ndx=>$value) {
		$insert_array['nscenario'] = $rgstreamdepletionscenario;
		$insert_array['timestepindex'] = $ndx+($startyear-1900)*$subtimestepcount;
		$insert_array['value'] = $value;
		pg_insert($pgconnection,$insert_array);
	}
}
//print_r($results);

?>
