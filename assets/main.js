



var transparentize= function(color, opacity) {
	var alpha = opacity === undefined ? 0.5 : 1 - opacity;
	return Color(color).alpha(alpha).rgbString();
};


var Results = {

	max : -1,
	count : 0,
	repositories : Array(),
	progressbar : null,
	granulatity : "",
	rangeMin : 0,
	rangeMax : 0,
	finalResults : null,

	init : function( count, granulatity, rangeMin, rangeMax ){
		Results.max = count;
		Results.granulatity = granulatity;
		Results.rangeMin = rangeMin;
		Results.rangeMax = rangeMax;
		Results.progressbar = $( "#progressbar" );
		Results.progressbar.prop( "aria-valuemax", Results.max );
		Results.progressbar.html( "0/" + Results.max )
	},

	inspectRepository : function( repositoryString, propToTrack ){
		$.ajax({
		    url: "./actions/inspectRepository.php",
		    data: {
		    	repository: repositoryString,
		    	granulatity: Results.granulatity,
		    	propToTrack: propToTrack,
		    	rangeMin: Results.rangeMin,
		    	rangeMax: Results.rangeMax
		    }
		}).done(function( data ){
			Results.computeResult( $.parseJSON( data ) );
		});
	},

	computeResult : function( repository ){
		Results.repositories.push( repository );
		Results.count++;
		Results.updateProgressbar();
		if( Results.count == Results.max ){
			Results.calculateFinalResults();
		}
	},

	updateProgressbar : function(){
		var percentage = parseInt( Results.count / Results.max * 100 );
		Results.progressbar.prop( "aria-valuenow", Results.count );
		Results.progressbar.html( Results.count + "/" + Results.max );
		Results.progressbar.css( "width", percentage + "%" );
	},

	calculateFinalResults : function(){
		$.ajax({
			type: "POST",
		    url: "./actions/calculateFinalResults.php",
		    data: {
		    	repositories: Results.repositories,
		    	granulatity: Results.granulatity,
		    	rangeMin: Results.rangeMin,
		    	rangeMax: Results.rangeMax
		    }
		}).done(function( data ){
			$( "#results" ).show();
			Results.finalResults = $.parseJSON( data );
			Results.drawGraphResults();
			Results.drawTableResults( 
				$( "#tableResultsByValueLabels" ), 
				$( "#tableResultsByValueValues" ),
				Results.finalResults.resultsByValue,
				Results.finalResults.max );
			Results.drawTableResults( 
				$( "#tableResultsContinuousLabels" ), 
				$( "#tableResultsContinuousValues" ),
				Results.finalResults.resultsContinuous,
				Results.finalResults.maxContinuous );
			Results.drawCharts();
		});
	},

	drawGraphResults : function(){
		Results.drawGraphResult( 
			$( "#graphUserInput" ), 
			"User input",
			Results.getRepositoriesResultCount( "all" ),
			"All repositories",
			"bg-primary col-sm no-list" );

		Results.drawGraphResult( 
			$( "#graphFilterDuplicates" ), 
			"Filter duplicates",
			Results.getRepositoriesResultCount( "duplicated" ),
			"Duplicated",
			"bg-warning col-sm margin-top",
			Results.getRepositoriesResult( "duplicated" ) );

		Results.drawGraphResult( 
			$( "#graphUniqueRepositories" ), 
			"Unique repositories",
			Results.getRepositoriesResultCount( "unique" ) - Results.getRepositoriesResultCount( "ignored" ),
			"After filters",
			"bg-secondary col-sm no-list" );

		Results.drawGraphResult( 
			$( "#graphFilterNames" ), 
			"Filter names",
			Results.getRepositoriesResultCount( "ignored" ),
			ignoredRepositoriesNames,
			"bg-warning col-sm margin-top",
			Results.getRepositoriesResult( "ignored" ) );

		var numRepoToAnalyse = Results.getRepositoriesResultCount( "unique" )
							 - Results.getRepositoriesResultCount( "withNoProjectDetected" )
							 - Results.getRepositoriesResultCount( "ignored" );
		
		Results.drawGraphResult( 
			$( "#graphRepositoriesToAnalyse" ), 
			"Repositories to analyse", 
			numRepoToAnalyse, 
			 "After pre-analyse", 
			 "bg-secondary col-sm no-list" );

		Results.drawGraphResult( 
			$( "#graphCheckProjectPattern" ), 
			"Check project pattern", 
			Results.getRepositoriesResultCount( "withNoProjectDetected" ), 
			"No android gradle detected", 
			"bg-warning col-sm with-list margin-top",
			Results.getRepositoriesResult( "withNoProjectDetected" ) );

		var numProjsToAnalyse = Results.getRepositoriesResultCount( "withChangesDetected" )
							  + Results.getRepositoriesResultCount( "withNoChangesDetected" );

		Results.drawGraphResult( 
			$( "#graphProjectsToAnalyse" ), 
			"Projects to analyse", 
			numProjsToAnalyse, 
			"Repositories can have multiple projects",
			"bg-secondary col-sm no-list" );

		Results.drawGraphResult( 
			$( "#graphFinalWithChanges" ), 
			"Final", 
			Results.getRepositoriesResultCount( "withChangesDetected" ), 
			"Projects with changes detected", 
			"bg-success col-sm with-list",
			Results.getRepositoriesResult( "withChangesDetected" ) );

		Results.drawGraphResult( 
			$( "#graphFinalWithNoChanges" ), 
			"Final", 
			Results.getRepositoriesResultCount( "withNoChangesDetected" ), 
			"No changes detected", 
			"bg-danger col-sm with-list",
			Results.getRepositoriesResult( "withNoChangesDetected" ) );
	},

	drawGraphResult : function( target, header, resultsCount, text, bgClass, repos = null ){
		output = "<div class=\"card " + bgClass + " text-white\">";
		output += "<div class=\"card-header\">" + header + "</div>";
		output += "<div class=\"card-body\">";
		output += "<h5 class=\"card-title\">" + resultsCount + "</h5>";
		output += "<p class=\"card-text\">" + text + "</p>";
		if( repos != null && repos.length > 0 ){
			output += "<ul>";
			for( var i = 0; i < repos.length; i++ ){
				if( repos[ i ][ "repoEntity" ] != null ){
					output += "<li><a href=\"https://github.com" + repos[ i ][ "repoEntity" ][ "repo" ] + "\" target=\"_blank\"> ";
					output += repos[ i ][ "repoEntity" ][ "repo" ] + ":" + repos[ i ][ "repoEntity" ][ "branch" ] + ":" + repos[ i ][ "repoEntity" ][ "folder" ];
					output += "</a></li>";	
				}
				else{
					output += "<li>" + repos[ i ][ "repositoryString" ] + "</li>";
				}
			}
			output += "</ul>";
		}
		output += "</div></div>";
		target.html( output );
	},

	drawTableResults : function( targetLabels, targetValues, valuesValues, max ){

		// labels
		var labels = "";
		for( var value in valuesValues ){
			labels += "<tr><td>" +value+ "</td></tr>";
		}
		targetLabels.html( labels );

		// values
		var values = "<thead><tr>";
		for( var period in Results.finalResults.resultsByPeriod ){
			values += "<th>" + period + "</th>";
		}
		values += "</tr></thead><tbody>";
		for( var value in valuesValues ){
			var results = valuesValues[ value ];
			values += "<tr>";
			for( var date in results ){
				var result = results[ date ];
				var colorForValue = Results.getColorForValue( result, max );
				values += "<td class=\"" + ( (result == 0) ? "light" : "" ) + "\"";
				values += "style=\"background-color:rgb(" + colorForValue + ")\">";
				values += result;
				values += "</td>";
			}
			values += "</tr>";
		}
		values += "</tbody>";
		targetValues.html( values );								
	},

	drawCharts : function(){

		// labels
		var chartLabels = Array();
		for( var period in Results.finalResults.resultsByPeriod ){
			chartLabels.push( period );
		}

		// Versions updates x time
		var chartDataChanges = {
			labels: chartLabels,
			datasets: Array()
		};
		var i = 0;
		for( var value in Results.finalResults.resultsByValue ){
			var obj = {
				label: value,
				borderColor: "rgb(" + resultGraphColors[ i ] + ")",
				backgroundColor: "rgb(" + resultGraphColors[ i ] + ")",
				fill: false,
				data: Array()
			};
			var results = Results.finalResults.resultsByValue[ value ];
			for( data in results ){
				var result = results[ data ] ? results[ data ] : "null";
				obj.data.push( result );
			}
			chartDataChanges.datasets.push( obj );
			i++;
		}
		var ctx = document.getElementById('canvasChart1').getContext('2d');
		window.myLine = Chart.Line(ctx, {
			data: chartDataChanges,
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
						type: 'linear',
						display: true,
						position: 'left',
						id: 'y-axis-1',
					}],
				}
			}
		});

		//  Apps versions x Time
		var chartDataContinuous = {
			labels: chartLabels,
			datasets: Array()
		};
		var i = 0;
		for( var value in Results.finalResults.resultsContinuous ){
			var obj = {
				label: value,
				borderColor: "rgb(" + resultGraphColors[ i ] + ")",
				backgroundColor: transparentize( "rgb(" + resultGraphColors[ i ] + ")", 0.8 ),
				data: Array()
			};
			var results = Results.finalResults.resultsContinuous[ value ];
			for( data in results ){
				obj.data.push( results[ data ] );
			}
			chartDataContinuous.datasets.push( obj );
			i++;
		}
		var chartContinuous = new Chart('chartContinuous', {
			type: 'line',
			data: chartDataContinuous,
			options: {
				title: {
					display: true,
					text: 'Apps versions x Time'
				},
				elements: {
					line: {
						tension: 0.000001
					}
				}
		    }
		});

		//  Apps versions x Time stacked
		var chartDataContinuousStacked = {
			labels: chartLabels,
			datasets: Array()
		};
		var i = 0;
		for( var value in Results.finalResults.resultsContinuous ){
			var obj = {
				label: value,
				borderColor: "rgb(" + resultGraphColors[ i ] + ")",
				backgroundColor: transparentize( "rgb(" + resultGraphColors[ i ] + ")", 0.2 ),
				data: Array()
			};
			var results = Results.finalResults.resultsContinuous[ value ];
			for( data in results ){
				obj.data.push( results[ data ] );
			}
			chartDataContinuousStacked.datasets.push( obj );
			i++;
		}
		var chartContinuousStacked = new Chart('chartContinuousStacked', {
			type: 'line',
			data: chartDataContinuousStacked,
			options: {
				title: {
					display: true,
					text: 'Apps versions x Time (stacked)'
				},
				elements: {
					line: {
						tension: 0.000001
					}
				},
				scales: {
					yAxes: [{
						stacked: true
					}]
				}
		    }
		});
	},

	getRepositoriesResultCount : function( key ){
		return Results.finalResults[ "repoCounts" ][ key ];
	},

	getRepositoriesResult : function( key ){
		return Results.finalResults[ "repoLists" ][ key ];
	},

	getColorForValue : function( value, max ){
		var index = Math.ceil( value / max * resultHeatColors.length ) - 1;
		return( resultHeatColors[ index ] );
	}
};