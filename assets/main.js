



var transparentize= function(color, opacity) {
	var alpha = opacity === undefined ? 0.5 : 1 - opacity;
	return Color(color).alpha(alpha).rgbString();
};

$( document ).ready(function(){
	var ctx = document.getElementById('canvasChart1').getContext('2d');
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
					type: 'linear',
					display: true,
					position: 'left',
					id: 'y-axis-1',
				}],
			}
		}
	});




    var optionsArea = {
		elements: {
			line: {
				tension: 0.000001
			}
		},
		scales: {
			yAxes: [{
				stacked: true
			}]
		},
		plugins: {
			filler: {
				propagate: false
			},
			'samples-filler-analyser': {
				target: 'chart-analyser'
			}
		}
    };

	var chartArea = new Chart('canvasChart2', {
		type: 'line',
		data: lineChartData2,
		options: optionsArea
	});

});