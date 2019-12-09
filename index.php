<?php
set_time_limit( 0 );
ini_set( "memory_limit", "256M" );

include "classes/Results.php";

$repositories = $_POST[ "repositories" ] ? $_POST[ "repositories" ] : array();
$granulatity = $_POST[ "granulatity" ];
$propToTrack = $_POST[ "propToTrack" ];
define( 'PARAM_TO_TRACK', $propToTrack );

// extract other repositories field
$otherProjects = $_POST[ "otherProjects" ];
$matches = array();
$regexp = "/(\/[a-zA-Z0-9\_\.\-]{1,}\/[a-zA-Z0-9\_\.\-]{1,}\:[a-zA-Z0-9\_\.\-]{0,}\:[a-zA-Z0-9\_\.\-]{0,})/";
preg_match_all( $regexp, $otherProjects, $matches );
if( sizeof( $matches ) && sizeof( $matches[0] ) ){
	$repositories = array_merge( $repositories, $matches[ 0 ] );
}
$resultsObj = new Results( $repositories, $granulatity );


function drawGraphResult( $header, $resultsCount, $text, $bgClass, $repos = null ){
	$output = '
	<div class="card '.$bgClass.' text-white">
		<div class="card-header">' .$header. '</div>
		<div class="card-body">
		    <h5 class="card-title">' .$resultsCount. '</h5>
		    <p class="card-text">' .$text. '</p>';
	if( $repos != null ){
		$output .= '<ul>';
		foreach( $repos as $repo ){
		    $output .= "<li><a href=\"https://github.com" .$repo->repoEntity->repo. "\" target=\"_blank\"> " .$repo->repoEntity->repo . ":" . $repo->repoEntity->branch . ":" . $repo->repoEntity->folder. "</a></li>";
		}
		$output .= '</ul>';
	}
	return $output. '</div></div>';
}
?>
<html>
<head>
	<title>Android Project Prop Tracker</title>

	<!-- jquery basics -->
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>

	<!-- bootstrap basics -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous" />
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

	<!-- lines graph -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>

	<!-- custom -->
	<link rel="stylesheet" href="./assets/style.css" />
	<script src="./assets/main.js"></script>

	<!-- scripted -->
	<script>
	var lineChartData = {
		labels: [
			<?php
			if( $resultsObj->hasResults ){
				foreach( $resultsObj->getResultsByPeriod() as $period => $results ){
					echo '"' . $period . '",';
				}
			}
			?>
		],
		datasets: [
			<?php
			if( $resultsObj->hasResults ){
				$i = 0;
				foreach( $resultsObj->getResultsByValue() as $value => $results ){
					echo "{  label:'".$value."',
					         borderColor: \"rgb(".$resultGraphColors[ $i ].")\",
					         backgroundColor: \"rgb(".$resultGraphColors[ $i ].")\",
					         fill:false,
					         data:[";

					foreach( $results as $result ){
						echo ( $result ? $result : "null" ) . ",";
					}
					echo "]},";
					$i++;
				}
			}
			?>
		]
	};
	
	var lineChartData2 = {
		labels: [
			<?php
			if( $resultsObj->hasResults ){
				foreach( $resultsObj->getResultsByPeriod() as $period => $results ){
					echo '"' . $period . '",';
				}
			}
			?>
		],

		datasets: [
			<?php
			if( $resultsObj->hasResults ){
				$i = 0;
				foreach( $resultsObj->getResultsContinuous() as $value => $results ){
					echo "{  label:'".$value."',
					         borderColor: \"rgb(".$resultGraphColors[ $i ].")\",
					         backgroundColor: transparentize( \"rgb(".$resultGraphColors[ $i ].")\" ),
					         data:[";

					foreach( $results as $result ){
						echo $result . ",";
					}
					echo "]},";
					$i++;
				}
			}
			?>
		]
		
	}
	</script>

</head>
<body>
<div class="container-fluid">
	<h1>Android Project Prop Tracker</h1>

	<div class="accordion" id="accordionExample">
		<div class="card">
			<div class="card-header" id="headingOne">
				<h2 class="mb-0">
					<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Form</button>
				</h2>
			</div>
			<div id="collapseOne" class="collapse <?=( $resultsObj->hasProjects ) ? "" : "show";?>" aria-labelledby="headingOne" data-parent="#accordionExample">
				<div class="card-body">

					<form method="post">
						<hr />
						<div class="row">
							<div class="col-sm">
								<label for="preTestedProjects">Pre tested repositories</label>
								<select multiple class="form-control" name="projects[]" size="10" id="preTestedProjects">
									<?php
									foreach( $reposOptions as $repo ){ 
										$value = $repo->repo . ":" . $repo->branch . ":" . $repo->folder;
									?>
										<option value="<?=$value;?>"
												<?=( array_search( $value, $repositories ) !== false ? "selected" : "" );?>>
											<?=$value;?>
										</option>
									<?php } ?>
								</select>
							</div>
							<div class="col-sm">
								<label for="otherProjects">Other repositories</label>
								<textarea class="form-control" rows="8" name="otherProjects" id="otherProjects" placeholder="/account/repository:branch:folder ... one repository per line ... no spaces allowed"><?=$otherProjects;?></textarea>
							</div>
						</div>
						<hr />
						<div class="row">
							<div class="col-sm">
								<label for="propToTrack">Property to track</label>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToTrack" id="minSdkVersion" value="minSdkVersion"
								  		 <?=( $propToTrack == "minSdkVersion" ? "checked" : "" );?> >
								  <label class="form-check-label" for="minSdkVersion">minSdkVersion</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToTrack" id="compileSdkVersion" value="compileSdkVersion"
								  		 <?=( $propToTrack == "compileSdkVersion" ? "checked" : "" );?> >
								  <label class="form-check-label" for="compileSdkVersion">compileSdkVersion</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToTrack" id="targetSdkVersion" value="targetSdkVersion"
								  		 <?=( $propToTrack == "targetSdkVersion" || !$propToTrack ? "checked" : "" );?>>
								  <label class="form-check-label" for="targetSdkVersion">targetSdkVersion</label>
								</div>
							</div>
							<div class="col-sm">
								<label for="granulatity">Granulatity</label>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="granulatity" id="granulatityMonthly" value="monthly"
								  		 <?=( $granulatity == "monthly" || !$granulatity ? "checked" : "" );?>>
								  <label class="form-check-label" for="granulatityMonthly">monthly</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="granulatity" id="granulatityQuartely" value="quartely"
								  		 <?=( $granulatity == "quartely" ? "checked" : "" );?>>
								  <label class="form-check-label" for="granulatityQuartely">quarterly</label>
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
			        <button class="btn btn-link collapsed <?=( $resultsObj->hasProjects ) ? "" : "disabled";?>" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Results</button>
			    </h2>
		    </div>
		    <div id="collapseTwo" class="collapse <?=( $resultsObj->hasProjects ) ? "show" : "";?>" aria-labelledby="headingTwo" data-parent="#accordionExample">
		    	<div class="card-body">
		    		
					<? if( $resultsObj->hasProjects ){ ?>

					<div class="card">
						<div class="card-body">
							<dl class="row">
								<dt class="col-sm-2">Tracking property:</dt>
								<dd class="col-sm-10"><?=$propToTrack;?></dd>
								<dt class="col-sm-2">Granulatity:</dt>
								<dd class="col-sm-10"><?=$granulatity;?></dd>
							</dl>

							<div class="resultsGraph">
								<div class="row">
									<?=
									drawGraphResult( "User input", 
													 $resultsObj->getRepositoriesResultCount( "all" ), 
													 "All repositories", 
													 "bg-primary col-sm-2" );
									?>
									<div class="separator col-sm-1">
										<br /><br /><br /><br /><br /><br />====>
										<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />
									</div>
									<?=
									drawGraphResult( "Unique repositories", 
													 $resultsObj->getRepositoriesResultCount( "unique" )
											             - $resultsObj->getRepositoriesResultCount( "ignored" ) , 
													 "After filters", 
													 "bg-secondary col-sm-2" );
									?>
									<div class="separator col-sm-1">
										<br /><br /><br /><br /><br /><br />====>
										<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />
									</div>
									<?=
									drawGraphResult( "Repositories to analyse", 
													 $resultsObj->getRepositoriesResultCount( "unique" )
											             - $resultsObj->getRepositoriesResultCount( "withNoProjectDetected" )
											             - $resultsObj->getRepositoriesResultCount( "ignored" ), 
													 "After pre-analyse", 
													 "bg-secondary col-sm-2" );
									?>
									<div class="separator col-sm-1">
										<br /><br /><br /><br /><br /><br />====>
										<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />
									</div>
									<?=
									drawGraphResult( "Final", 
													 $resultsObj->getRepositoriesResultCount( "withChangesDetected" ), 
													 "Repositories with changes detected", 
													 "bg-success col-sm-3",
													 $resultsObj->getRepositoriesResult( "withChangesDetected" ) );
									?>
								</div>

								<div class="row">
									<div class="col-sm-2"></div>
									<div class="separator col-sm-1">||<br />||<br />||</div>
									<div class="col-sm-2"></div>
									<div class="separator col-sm-1">||<br />||<br />||</div>
									<div class="col-sm-2"></div>
									<div class="separator col-sm-1">||<br />||<br />||</div>
								</div>
								
								<div class="row">
									<?=
									drawGraphResult( "Filter duplicates", 
													 $resultsObj->getRepositoriesResultCount( "duplicated" ), 
													 "Duplicated", 
													 "bg-warning col-sm-2" );
									?>
									<div class="separator col-sm-1">
										||<br />||<br />||<br />||<br />||<br />
										<====>
									</div>
									<?=
									drawGraphResult( "Filter names", 
													 $resultsObj->getRepositoriesResultCount( "ignored" ), 
													 implode(", ", $ignoredRepositoriesNames), 
													 "bg-warning col-sm-2",
													 $resultsObj->getRepositoriesResult( "ignored" ) );
									?>
									<div class="separator col-sm-1">
										||<br />||<br />||<br />||<br />||<br />
										&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;==>
									</div>
									<?=
									drawGraphResult( "Check project pattern", 
													 $resultsObj->getRepositoriesResultCount( "withNoProjectDetected" ), 
													 "No android gradle detected", 
													 "bg-warning col-sm-2",
													 $resultsObj->getRepositoriesResult( "withNoProjectDetected" ) );
									?>
									<div class="separator col-sm-1">
										||<br />||<br />||<br />||<br />||<br />
										&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;==>
									</div>
									<?=
									drawGraphResult( "Final", 
													 $resultsObj->getRepositoriesResultCount( "withNoChangesDetected" ), 
													 "No changes detected", 
													 "bg-danger col-sm-3",
													 $resultsObj->getRepositoriesResult( "withNoChangesDetected" ) );
									?>
								</div>
							</div>

						</div>
					</div>
					<br />
						
					<div>
						<div style="float:left; width: 5%;">
							<table class="resultsTable table">
								<thead>
									<tr>
										<th>API levels</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach( $resultsObj->getResultsByValue() as $value => $results ){ ?>
										<tr>
											<td><?=$value;?></td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<div style="float:right; width: 95%;" class="resultsTableHolder">
							<table class="resultsTable table">
								<thead>
									<tr>
										<?php foreach( $resultsObj->getResultsByPeriod() as $period => $results ){ ?>
											<th><?=$period;?></th>
										<?php } ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach( $resultsObj->getResultsByValue() as $value => $results ){ ?>
										<tr>
											<? foreach( $results as $result ){ ?>
												<td class="<?=($result == 0) ? "light" : "";?>"
													style="background-color:rgb(<?=$resultsObj->getColorForValue( $result, $resultHeatColors );?>)">
													<?=$result;?>
												</td>
											<? } ?>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						
					</div>

					<? } ?>

					<div style="width:98%; margin: 1%;">
						<canvas id="canvasChart1"></canvas>
					</div>

					<div style="width:98%; margin: 1%;">
						<canvas id="canvasChart2"></canvas>
					</div>

		    	</div>
		    </div>

		</div>

	</div><!-- accordion -->
</div><!-- container -->


</body>
</html>