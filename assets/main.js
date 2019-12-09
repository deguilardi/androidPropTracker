



var transparentize= function(color, opacity) {
	var alpha = opacity === undefined ? 0.5 : 1 - opacity;
	return Color(color).alpha(alpha).rgbString();
};

$( document ).ready(function(){
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


	var chartContinuousStacked = new Chart('chartContinuousStacked', {
		type: 'line',
		data: chartDataContinuousStacked,
		options: {
			title: {
				display: true,
				text: 'Apps versions x Time'
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

});