<?php
set_time_limit( 0 );
ini_set( "memory_limit", "1024M" );
chdir( "../" );

include "classes/Results.php";

$repositories = $_POST[ "repositories" ];
$granulatity = $_POST[ "granulatity" ];
$rangeMin = $_POST[ "rangeMin" ];
$rangeMax = $_POST[ "rangeMax" ];
define( 'RANGE_MIN', $rangeMin );
define( 'RANGE_MAX', $rangeMax );

$results = new Results( $repositories, $granulatity );
echo $results->toJson();
?>