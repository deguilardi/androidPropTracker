<?php

include "classes/Results.php";

$projects = $_POST[ "projects" ];
$granulatity = $_POST[ "granulatity" ];
$propToTrack = $_POST[ "propToTrack" ];
define( 'PARAM_TO_TRACK', $propToTrack );


$resultsObj = new Results( $projects, $granulatity );
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
	</script>

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
			<div id="collapseOne" class="collapse <?=( $resultsObj->hasProjects ) ? "" : "show";?>" aria-labelledby="headingOne" data-parent="#accordionExample">
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
								<label for="propToTrack">Property to track</label>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToTrack" id="minSdkVersion" value="minSdkVersion" >
								  <label class="form-check-label" for="minSdkVersion">minSdkVersion</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToTrack" id="compileSdkVersion" value="compileSdkVersion" >
								  <label class="form-check-label" for="compileSdkVersion">compileSdkVersion</label>
								</div>
								<div class="form-check">
								  <input class="form-check-input" type="radio" name="propToTrack" id="targetSdkVersion" value="targetSdkVersion" checked>
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
			        <button class="btn btn-link collapsed <?=( $resultsObj->hasProjects ) ? "" : "disabled";?>" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Results</button>
			    </h2>
		    </div>
		    <div id="collapseTwo" class="collapse <?=( $resultsObj->hasProjects ) ? "show" : "";?>" aria-labelledby="headingTwo" data-parent="#accordionExample">
		    	<div class="card-body">
		    		
					<? if( $resultsObj->hasProjects ){ ?>
						
					<div class="resultsTableHolder">
						<table class="resultsTable">
							<thead>
								<tr>
									<th>API levels</th>
									<?php foreach( $resultsObj->getResultsByPeriod() as $period => $results ){ ?>
										<th><?=$period;?></th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach( $resultsObj->getResultsByValue() as $value => $results ){ ?>
									<tr>
									<td><?=$value;?></td>
										<? foreach( $results as $result ){ ?>
											<td class="<?=($result == 0) ? "light" : "";?>"><?=$result;?></td>
										<? } ?>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>

					<? } ?>

					<div style="width:98%; margin: 1%;">
						<canvas id="canvas"></canvas>
					</div>

		    	</div>
		    </div>

		</div>

	</div><!-- accordion -->
</div><!-- container -->


</body>
</html>