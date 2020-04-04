<?php
set_time_limit( 0 );

// prevent osx to miss some info
usleep( 100 );

chdir( "../" );

include "classes/Result.php";

$repository = $_REQUEST[ "repository" ];
$granulatity = $_REQUEST[ "granulatity" ];
$propToTrack = $_REQUEST[ "propToTrack" ];
$rangeMin = $_REQUEST[ "rangeMin" ];
$rangeMax = $_REQUEST[ "rangeMax" ];
define( 'PARAM_TO_TRACK', $propToTrack );
define( 'RANGE_MIN', $rangeMin );
define( 'RANGE_MAX', $rangeMax );

$result = new Result( $repository, $granulatity );
echo $result->toJson();
?>