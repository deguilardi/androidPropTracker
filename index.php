<?php
set_time_limit( 0 );
ini_set( "memory_limit", "1024M" );
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include "classes/Results.php";

$repositories = $_POST[ "repositories" ] ? $_POST[ "repositories" ] : array();
$granulatity = $_POST[ "granulatity" ];
$propToTrack = $_POST[ "propToTrack" ];
$rangeMin = $_POST[ "rangeMin" ];
$rangeMax = $_POST[ "rangeMax" ];

// extract other repositories field
$otherProjects = $_POST[ "otherProjects" ];
$matches = array();
$regexp = "/(\/[a-zA-Z0-9\_\.\-]{1,}\/[a-zA-Z0-9\_\.\-]{1,}\:[a-zA-Z0-9\_\.\-]{0,}\:[a-zA-Z0-9\_\.\-]{0,})/";
preg_match_all( $regexp, $otherProjects, $matches );
if( sizeof( $matches ) && sizeof( $matches[0] ) ){
	$repositories = array_merge( $repositories, $matches[ 0 ] );
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

	var resultGraphColors = ["<?=implode( "\",\"" , $resultGraphColors );?>"];

	var resultHeatColors = ["<?=implode( "\",\"" , $resultHeatColors );?>"];

	var ignoredRepositoriesNames = ["<?=implode( "\",\"" , $ignoredRepositoriesNames );?>"];

	$( document ).ready(function(){
		<?php
		if( sizeof( $repositories ) ){
			echo "Results.init( ".sizeof( $repositories ).", \"".$granulatity."\", ".$rangeMin.", ".$rangeMax." );";
			foreach( $repositories as $repository ){ ?>
				Results.inspectRepository( "<?=$repository;?>", "<?=$propToTrack;?>" );
		<?php } } ?>
	});
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
			<div id="collapseOne" class="collapse <?=( sizeof( $repositories ) ) ? "" : "show";?>" aria-labelledby="headingOne" data-parent="#accordionExample">
				<div class="card-body">

					<form method="post">
						<hr />
						<div class="row">
							<!--<div class="col-sm">
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
							</div>-->
							<div class="col-sm">
								<label for="otherProjects">Repositories</label>
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
								<br />
								Property range
								<div class="form-row">
									<div class="col-sm input-group input-group-sm">
										<div class="input-group-prepend">
										    <span class="input-group-text">min</span>
										</div>
									    <input class="form-control" type="text" name="rangeMin" id="rangeMin" value="<?=( $rangeMin ? $rangeMin : "21" );?>" />
									</div>
									<div class="col-sm input-group input-group-sm">
										<div class="input-group-prepend">
										    <span class="input-group-text">max</span>
										</div>
									    <input class="form-control" type="text" name="rangeMax" id="rangeMax" value="<?=( $rangeMax ? $rangeMax : "29" );?>" />
									</div>
								</div>
							</div>
							<!--<div class="col-sm">
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
							</div>-->
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
			        <button class="btn btn-link collapsed <?=( sizeof( $repositories ) ) ? "" : "disabled";?>" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Results</button>
			    </h2>
		    </div>
		    <div id="collapseTwo" class="collapse <?=( sizeof( $repositories ) ) ? "show" : "";?>" aria-labelledby="headingTwo" data-parent="#accordionExample">

				<div class="progress">
				  	<div id="progressbar"
				  	     class="progress-bar" 
				  		 role="progressbar" 
				  		 style="width: 0%;" 
				  		 aria-valuenow="0" 
				  		 aria-valuemin="0" 
				  		 aria-valuemax="100">0/x</div>
				</div>

		    	<div class="card-body" id="results" style="display:none">
		    		
					<? if( sizeof( $repositories ) ){ ?>

					<div class="card">
						<div class="card-body">
							<dl class="row">
								<dt class="col-sm-2">Tracking property:</dt>
								<dd class="col-sm-10"><?=$propToTrack;?></dd>
								<dt class="col-sm-2">Granulatity:</dt>
								<dd class="col-sm-10"><?=$granulatity;?></dd>
							</dl>

							<div class="resultsGraph container-fluid">
								<div class="row no-gutters justify-content-center">
									<div class="col-sm-2">
										<span id="graphUserInput"></span>
										<br />
										<span id="graphFilterDuplicates"></span>
									</div>
									<div class="separator col-sm-auto">
										<br /><br /><br /><br />====>
										<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />
										<====>
									</div>
									<div class="col-sm-2">
										<span id="graphUniqueRepositories"></span>
										<br />
										<span id="graphFilterNames"></span>
									</div>
									<div class="separator col-sm-auto">
										<br /><br /><br /><br />====>
										<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />
										&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;==>
									</div>
									<div class="col-sm-2">
										<span id="graphRepositoriesToAnalyse"></span>
										<br />
										<span id="graphCheckProjectPattern"></span>
									</div>
									<div class="separator col-sm-auto">
										<br /><br /><br /><br />====>
										<br /><br /><br /><br /><br /><br /><br /><br />
									</div>
									<div class="col-sm-2">
										<span id="graphProjectsToAnalyse"></span>
									</div>
									<div class="separator col-sm-auto">
										<br /><br /><br /><br />====>
										<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />||<br />
										&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;==>
									</div>
									<div class="col-sm-2">
										<span id="graphFinalWithChanges"></span>
										<br />
										<span id="graphFinalWithNoChanges"></span>
									</div>
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
								<tbody id="tableResultsByValueLabels"></tbody>
							</table>
						</div>
						<div style="float:right; width: 95%;" class="resultsTableHolder">
							<table class="resultsTable table" id="tableResultsByValueValues"></table>
						</div>
					</div>

					<div style="width:98%; margin: 1%;">
						<canvas id="canvasChart1"></canvas>
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
								<tbody id="tableResultsContinuousLabels"></tbody>
							</table>
						</div>
						<div style="float:right; width: 95%;" class="resultsTableHolder">
							<table class="resultsTable table" id="tableResultsContinuousValues"></table>
						</div>
					</div>

					<div style="width:98%; margin: 1%;">
						<canvas id="chartContinuous"></canvas>
					</div>

					<div style="width:98%; margin: 1%;">
						<canvas id="chartContinuousStacked"></canvas>
					</div>

					<? } ?>

		    	</div>
		    </div>

		</div>

	</div><!-- accordion -->
</div><!-- container -->


</body>
</html>