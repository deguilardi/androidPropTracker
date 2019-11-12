<?php

include "classes/Repository.php";

$reposOptions = array(

	// facebook
	new RepositoryEntity( "/facebook/device-year-class", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/facebook-android-sdk", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/flipper", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/fresco", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/screenshot-tests-for-android", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/shimmer-android", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/stetho", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/TextLayoutBuilder", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/yoga", GIT_BRANCH_DEFAULT, "" ),

	// google
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "BasicSample" ),
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "PersistenceContentProviderSample" ),
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "PersistenceMigrationsSample" ),
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "WorkManagerSample" ),
	new RepositoryEntity( "/android/architecture-samples", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/plaid", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/sunflower", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/uamp", GIT_BRANCH_DEFAULT, "" ),

	// others
	new RepositoryEntity( "/chrisbanes/tivi", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/PierfrancescoSoffritti/android-youtube-player", GIT_BRANCH_DEFAULT, "" ), 
	new RepositoryEntity( "/DrKLO/Telegram", GIT_BRANCH_DEFAULT, "" ), 

	// work with no result
	// new RepositoryEntity( "/android/topeka", GIT_BRANCH_DEFAULT, "" ),
	// new RepositoryEntity( "/android/storage-samples", GIT_BRANCH_DEFAULT, "ActionOpenDocument" ),
	// new RepositoryEntity( "/android/app-bundle-samples", GIT_BRANCH_DEFAULT, "DynamicCodeLoadingKotlin" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "AdvancedImmersiveMode" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "ElevationBasic" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "Notifications" ),
	// new RepositoryEntity( "/facebook/conceal", GIT_BRANCH_DEFAULT, "" ),
	// new RepositoryEntity( "/facebook/SoLoader", GIT_BRANCH_DEFAULT, "" ),
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
$granulatity = $_POST[ "granulatity" ];
$propToExtract = $_POST[ "propToExtract" ];
define( 'PARAM_TO_EXTRACT', $propToExtract );


function getPeriodWithGranulatity( $value, $granulatity ){
	if( $granulatity == "quartely" ){
		$year = substr( $value, 0, 4 );
		$month = (int) substr( $value, -2 );
		$quarter = ceil( $month / 3 );
		return $year . "-q" . $quarter;
	}
	else{
		return $value;
	}
}


if( @sizeof( $projects) ){
	$repos = array();
	foreach( $projects as $project ){
		$parts = explode( ":", $project );
		$repoEntity = new RepositoryEntity( $parts[ 0 ], $parts[ 1 ], $parts[ 2 ] );
		$repos[] = new Repository( $repoEntity );
	}

	// determine min and max months
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

	// determine diff in months
	$datetimeMin = date_create( $minMonth . "-01" ); 
	$datetimeMax = date_create( $maxMonth . "-01" );
	$interval = date_diff( $datetimeMin, $datetimeMax );
	$diffInMonths = $interval->format( '%y' ) * 12 + $interval->format( '%m' ) + 1;

	// init empty results array
	$resultsByPeriod = array();
	$year = date_format( $datetimeMin, "Y" );
	$month = date_format( $datetimeMin, "m" );
	for( $i = 0; $i < $diffInMonths; $i++ ){
		$key = $year . "-" . str_pad( $month, 2, '0', STR_PAD_LEFT );
		$key = getPeriodWithGranulatity( $key, $granulatity );
		$resultsByPeriod[ $key ] = array();
		$month++;
		if( $month > 12 ){
			$month = 1;
			$year++;
		}
	}

	// distribute results into period
	foreach( $repos as $repo ){
		foreach( $repo->propertyChanges[ $granulatity ] as $period => $changes ){
			foreach( $changes as $key => $change ){
				$resultsByPeriod[ $period ][ $key ] += $change;
			}

		}
	}

	// results grouped by value
	$resultsByValue = array();
	foreach( $repos as $repo ){
		foreach( $repo->propertyChanges[ $granulatity ] as $period => $changes ){
			foreach( $changes as $key => $change ){
				if( $key == GradleFile::NOT_LOADED ){ continue; }

				// initialize with zeroes
				if( !$resultsByValue[ $key ] ){
					foreach( $resultsByPeriod as $key2 => $result ){
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
	<script   src="https://code.jquery.com/jquery-3.4.1.min.js"   integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="   crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="./style.css" />
</head>
<body>

<div class="container">
	<h1>Android Project Prop Tracker</h1>

	<div class="accordion" id="accordionExample">
		<div class="card">
			<div class="card-header" id="headingOne">
				<h2 class="mb-0">
					<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Form</button>
				</h2>
			</div>
			<div id="collapseOne" class="collapse <?=( @sizeof( $projects) ) ? "" : "show";?>" aria-labelledby="headingOne" data-parent="#accordionExample">
				<div class="card-body">

					<form method="post">
						<hr />
						<div class="row">
							<div class="col-sm">
								<label for="preTestedProjects">Pre tested projects</label>
								<select multiple class="form-control" name="projects[]" size="10" id="preTestedProjects">
									<?php
									foreach( $reposOptions as $repo ){ 
										$value = $repo->repo . ":" . $repo->branch . ":" . $repo->folder;
									?>
										<option value="<?=$value;?>"><?=$value;?></option>
									<?php } ?>
								</select>
							</div>
							<div class="col-sm">
								<label for="otherProjects">Other projects</label>
								<textarea class="form-control" rows="8" id="otherProjects" placeholder="@todo" disabled></textarea>
							</div>
						</div>
						<hr />
						<div class="row">
							<div class="col-sm">
								<label for="propToExtract">Property to extract</label>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToExtract" id="minSdkVersion" value="minSdkVersion" >
								  <label class="form-check-label" for="minSdkVersion">minSdkVersion</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToExtract" id="compileSdkVersion" value="compileSdkVersion" >
								  <label class="form-check-label" for="compileSdkVersion">compileSdkVersion</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToExtract" id="targetSdkVersion" value="targetSdkVersion" checked>
								  <label class="form-check-label" for="targetSdkVersion">targetSdkVersion</label>
								</div>
							</div>
							<div class="col-sm">
								<label for="granulatity">Granulatity</label>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="granulatity" id="granulatityMonthly" value="monthly" checked>
								  <label class="form-check-label" for="granulatityMonthly">monthly</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="granulatity" id="granulatityQuartely" value="quartely">
								  <label class="form-check-label" for="granulatityQuartely">quartely</label>
								</div>
							</div>
						</div>
						<hr />
						<button type="submit" class="btn btn-primary">Submit</button>
					</form>

	      		</div>
	    	</div>
	  	</div>
	  	<div class="card">
		    <div class="card-header" id="headingTwo">
			    <h2 class="mb-0">
			        <button class="btn btn-link collapsed <?=( @sizeof( $projects) ) ? "" : "disabled";?>" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Results</button>
			    </h2>
		    </div>
		    <div id="collapseTwo" class="collapse <?=( @sizeof( $projects) ) ? "show" : "";?>" aria-labelledby="headingTwo" data-parent="#accordionExample">
		    	<div class="card-body">
		    		
					<? if( @sizeof( $projects) ){ ?>
						
					<div class="resultsTableHolder">
						<table class="resultsTable">
							<thead>
								<tr>
									<th>API levels</th>
									<?php foreach( $resultsByPeriod as $period => $results ){ ?>
										<th><?=$period;?></th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach( $resultsByValue as $value => $results ){ ?>
									<tr>
									<td><?=$value;?></td>
										<? foreach( $results as $result ){ ?>
											<td><?=$result;?></td>
										<? } ?>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>

					<div style="width:98%; margin: 1%;">
						<canvas id="canvas"></canvas>
					</div>

					<script>
					var lineChartData = {
						labels: [
							<?php
							foreach( $resultsByPeriod as $period => $results ){
								echo '"' . $period . '",';
							}
							?>
						],
						datasets: [
							<?php
							$i = 0;
							foreach( $resultsByValue as $value => $results ){
								echo "{
									     label:'".$value."',
								         borderColor: \"rgb(".$colors[ $i ].")\",
								         backgroundColor: \"rgb(".$colors[ $i ].")\",
								         fill:false,
								         data:[";

								foreach( $results as $result ){
									echo ( $result ? $result : "null" ) . ",";
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

		    	</div>
		    </div>
		  </div>
	</div>


</div>


</body>
</html>