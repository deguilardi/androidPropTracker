<?php

include "classes/Repository.php";

$reposOptions = array(

	// facebook
	new RepositoryEntity( "/facebook/facebook-android-sdk", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/flipper", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/screenshot-tests-for-android", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/yoga", GIT_BRANCH_DEFAULT, "" ),

	// google
	new RepositoryEntity( "/android/architecture-samples", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/plaid", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/sunflower", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/uamp", GIT_BRANCH_DEFAULT, "" ),

	// others
	new RepositoryEntity( "/chrisbanes/tivi", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/PierfrancescoSoffritti/android-youtube-player", GIT_BRANCH_DEFAULT, "" ), 
	new RepositoryEntity( "/DrKLO/Telegram", GIT_BRANCH_DEFAULT, "" ), 

	// @TODO different pattern
	// new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "PagingSample" ),

	// work with no result
	// new RepositoryEntity( "/android/topeka", GIT_BRANCH_DEFAULT, "" ),
	// new RepositoryEntity( "/android/storage-samples", GIT_BRANCH_DEFAULT, "ActionOpenDocument" ),
	// new RepositoryEntity( "/android/app-bundle-samples", GIT_BRANCH_DEFAULT, "DynamicCodeLoadingKotlin" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "AdvancedImmersiveMode" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "ElevationBasic" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "Notifications" ),
);

$colors = array(
	"255,0,0",
	"0,255,0",
	"0,0,255",
	"255,255,0",
	"255,0,255",
	"0,255,255",
	"128,0,0",
	"0,128,0",
	"0,0,128",
	"128,128,0",
	"128,0,128",
	"0,128,128"
);

$projects = $_POST[ "projects" ];
if( @sizeof( $projects) ){
	$repos = array();
	foreach( $projects as $project ){
		$parts = explode( ":", $project );
		$repoEntity = new RepositoryEntity( $parts[ 0 ], $parts[ 1 ], $parts[ 2 ] );
		$repos[] = new Repository( $repoEntity );
	}
	// init empty results array
	$results = array();
	$minMonth = date( "Y-m" );
	$maxMonth = "2006-01";
	foreach( $repos as $repo ){
		$repoMinMonth = array_key_first( $repo->propertyChanges[ "monthly" ] );
		$minMonth = $repoMinMonth < $minMonth
		          ? $repoMinMonth
		          : $minMonth;
		$repoMaxMonth = array_key_last( $repo->propertyChanges[ "monthly" ] );
		$maxMonth = $repoMaxMonth > $maxMonth
		          ? $repoMaxMonth
		          : $maxMonth;
	}
	$maxMonth = ( $maxMonth == "2006-01" ? $minMonth : $maxMonth );
	$datetimeMin = date_create( $minMonth . "-01" ); 
	$datetimeMax = date_create( $maxMonth . "-01" );
	$interval = date_diff( $datetimeMin, $datetimeMax );
	$diffInMonths = $interval->format( '%y' ) * 12 + $interval->format( '%m' );
	$year = date_format( $datetimeMin, "Y" );
	$month = date_format( $datetimeMin, "m" );
	for( $i = 0; $i < $diffInMonths; $i++ ){
		$results[ $year . "-" . str_pad( $month, 2, '0', STR_PAD_LEFT ) ] = array();
		$month++;
		if( $month > 12 ){
			$month = 1;
			$year++;
		}
	}

	// distribute results into months
	foreach( $repos as $repo ){
		foreach( $repo->propertyChanges[ "monthly" ] as $period => $changes ){
			foreach( $changes as $key => $change ){
				$results[ $period ][ $key ] += $change;
			}

		}
	}

	// results grouped by value
	$resultsByValue = array();
	foreach( $repos as $repo ){
		foreach( $repo->propertyChanges[ "monthly" ] as $period => $changes ){
			foreach( $changes as $key => $change ){
				if( $key == GradleFile::NOT_LOADED ){ continue; }

				// initialize with zeroes
				if( !$resultsByValue[ $key ] ){
					foreach( $results as $key2 => $result ){
						$resultsByValue[ $key ][ $key2 ] = 0;
					}
				}

				$resultsByValue[ $key ][ $period ] += $change;
			}
		}
	}
    ksort( $resultsByValue );
}


?>
<html>
<head>
	<title>Android Project Prop Tracker</title>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
	<style>
	canvas {
		-moz-user-select: none;
		-webkit-user-select: none;
		-ms-user-select: none;
	}
	</style>
</head>
<body>
<h1>Android Project Prop Tracker</h1>
<form method="post">
	<h3>Tested projects:</h3>
	<select multiple name="projects[]" size="<?=sizeof($reposOptions);?>">
		<?php
		foreach( $reposOptions as $repo ){ 
			$value = $repo->repo . ":" . $repo->branch . ":" . $repo->folder;
		?>
			<option value="<?=$value;?>"><?=$value;?></option>
		<?php } ?>
	</select>
	<br />
	<br />
	<input type="submit" value="submit" />
</form>

<? if( @sizeof( $projects) ){ ?>

<h2>Results</h2>
<div style="width:90%; margin: 5%;">
	<canvas id="canvas"></canvas>
</div>

<script>
var lineChartData = {
	labels: [
		<?php
		foreach( $results as $period => $result ){
			echo '"' . $period . '",';
		}
		?>
	],
	datasets: [
		<?php
		$i = 0;
		foreach( $resultsByValue as $period => $results ){
			echo "{
				     label:'".$period."',
			         borderColor: \"rgb(".$colors[ $i ].")\",
			         backgroundColor: \"rgb(".$colors[ $i ].")\",
			         fill:false,
			         data:[";

			foreach( $results as $result ){
				echo $result . ",";
			}
			echo "]},";
			$i++;
		}
		?>]
};

window.onload = function() {
	var ctx = document.getElementById('canvas').getContext('2d');
	window.myLine = Chart.Line(ctx, {
		data: lineChartData,
		options: {
			responsive: true,
			hoverMode: 'index',
			stacked: false,
			title: {
				display: true,
				text: 'Versions updates x Time'
			},
			scales: {
				yAxes: [{
					type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
					display: true,
					position: 'left',
					id: 'y-axis-1',
				}, {
					type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
					display: true,
					position: 'right',
					id: 'y-axis-2',

					// grid line settings
					gridLines: {
						drawOnChartArea: false, // only want the grid lines for one axis to show up
					},
				}],
			}
		}
	});
};
</script>

<? } ?>

</body>
</html>