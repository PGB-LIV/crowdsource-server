function formatTime(duration)
{
	var ms = duration % 1000;
	duration = Math.floor(duration / 1000);

	var hours = Math.floor(duration / 3600);
	duration = duration - (hours * 3600);

	var minutes = Math.floor(duration / 60);
	duration = duration - (minutes * 60);

	hours = hours + "";
	minutes = minutes + "";
	var seconds = duration + "";
	
	return hours.padStart(2, '0') + ':' + minutes.padStart(2, '0') + ':' + seconds.padStart(2, '0');
}

function updateResults() {
	var jobId = $("#job_id").val();

	if (jobId === undefined || jobId === "") {
		return;
	}

	if (!$("#download").is(":visible")) {
		setTimeout(updateResults, 10000);
		return;
	}

	$
			.getJSON(
					"index.php?id="
							+ jobId + "&page=json_job_status",
					function(data) {

						var content = "<header><h3>Job " + jobId + " ("
								+ data.file + ")</h3></header>";

						switch (data.status) {
						case 'NOT_FOUND':
							content += "<p>Your search identifier was not found.<br />";
							content += "Please enter you have entered the correct identifier.<p>";
							break;
						case 'NEW':
							content += "<p>Your search is currently queued.<br />";
							content += "You will automatically move up in the queue once the previous job completes.<p>";
							break;
						case 'FASTA':
						case 'RAW':
						case 'WORKUNITS':
							content += "<p>Your search is now being prepared for processing and should begin shortly.</p>";
							break;
						case 'PROCESSING':
							var progress = Math
									.floor((data.progress_curr / data.progress_max) * 100);
							if (progress < 100) {
								content += "<p>Your search is now being processed. Currently "
										+ progress + "% complete.</p>";
							} else {
								content += "<p>Your search is complete. The results are now being generated.</p>";
							}

							break;
						case "COMPLETE":
							content += "<p>Your search is complete.<br />";
							content += '<a href="http://pgb.liv.ac.uk/shiny/ash/?id='
									+ jobId + '">Visualise results</a><br />';
							content += '<a href="results/'
									+ jobId
									+ '/psm.mzid">Download results (mzIdentML)</a></p>';
							break;

						default:
							content += "Unknown state?";
							break;
						}

						var today = new Date();
						content += '<p style="text-align: right; font-size: 50%">As at '
								+ today.toLocaleString("en-gb") + '</p>';

						$(".results_here").empty();
						$(".results_here").append(content);

						if (data.status !== "COMPLETE"
								&& data.status !== "NOT_FOUND") {
							setTimeout(updateResults, 10000);
						}
					});
}

function updateStatus() {
	$
			.getJSON(
					"index.php?page=json_monitor_status",
					function(data) {
						var content = "<header><h3>Current Job</h3></header>";

						if (data.job_current === 'NONE') {
							content += '<p>Dracula is currently idle.</p>';
						} else {
							content += '<p>Dracula is currently consuming job '
									+ data.job_current + ' ('
									+ data.job_progress + '% complete) since '
									+ data.job_start + '.</p>';
						}

						content += "<header><h3>Queued Jobs</h3></header>";

						if (data.jobs_queued > 0) {
							content += '<p>There are currently '
									+ data.jobs_queued
									+ ' jobs in the queue, next job is estimated to start in '
									+ data.job_next_start + ' hours</p>';
						} else {
							content += '<p>There are currently no jobs queued for processing.</p>';
						}

						content += "<header><h3>Completed Jobs</h3></header>";

						content += '<p>Dracula has devoured '
								+ data.jobs_completed + ' jobs since '
								+ data.first_job_at + ', analysing '
								+ data.scans_completed
								+ ' work units. Last job was completed at '
								+ data.last_job_at + '.</p>';

						content += '<table><thead><tr><th>Spectra</th><th>CPU Time</th><th>Run Time</th><th>Bandwidth (GB)</th></tr></thead><tbody>';

						for (var i = 0; i < data.last_jobs.length; i++) {

							content += '<tr>';
							content += '<td>'
									+ data.last_jobs[i].precursors
									+ '</td>';
							content += '<td>' + formatTime(data.last_jobs[i].cpu_time) + '</td>';
							content += '<td>' + formatTime(data.last_jobs[i].run_time) + '</td>';
							content += '<td>' + Math.round((((data.last_jobs[i].data / 1024) / 1024) /1024) * 100) / 100
									+ '</td>';
							content += '</tr>';
						}

						content += '</tbody></table>';

						imageSeries.data = data.locations;
						chart.validateData();
						
						var today = new Date();
						content += '<p style="text-align: right; font-size: 50%">As at '
								+ today.toLocaleString("en-gb") + '</p>';
						
						$(".status_here").empty();
						$(".status_here").append(content);

						setTimeout(updateStatus, 60000);
					});
}

var chart;
var imageSeries;
$(document).ready(function() {
	// Themes begin
	am4core.useTheme(am4themes_animated);
	// Themes end

	/**
	 * Define SVG path for target icon
	 */
	var targetSVG = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";

	// Create map instance
	chart = am4core.create("nodeMap", am4maps.MapChart);

	// Set map definition
	chart.geodata = am4geodata_worldLow;

	// Set projection
	chart.projection = new am4maps.projections.Miller();

	// Create map polygon series
	var polygonSeries = chart.series.push(new am4maps.MapPolygonSeries());

	// Exclude Antartica
	polygonSeries.exclude = ["AQ"];

	// Make map load polygon (like country names) data from
	// GeoJSON
	polygonSeries.useGeodata = true;

	// Configure series
	var polygonTemplate = polygonSeries.mapPolygons.template;
	polygonTemplate.strokeOpacity = 0.5;
	polygonTemplate.nonScalingStroke = true;

	// create capital markers
	imageSeries = chart.series.push(new am4maps.MapImageSeries());

	// define template
	var imageSeriesTemplate = imageSeries.mapImages.template;
	var circle = imageSeriesTemplate.createChild(am4core.Sprite);
	circle.scale = 0.4;
	circle.fill = new am4core.InterfaceColorSet().getFor("alternativeBackground");
	circle.path = targetSVG;
	// what about scale...

	// set propertyfields
	imageSeriesTemplate.propertyFields.latitude = "latitude";
	imageSeriesTemplate.propertyFields.longitude = "longitude";

	imageSeriesTemplate.horizontalCenter = "middle";
	imageSeriesTemplate.verticalCenter = "middle";
	imageSeriesTemplate.align = "center";
	imageSeriesTemplate.valign = "middle";
	imageSeriesTemplate.width = 8;
	imageSeriesTemplate.height = 8;
	imageSeriesTemplate.nonScaling = true;
	imageSeriesTemplate.tooltipText = "{title}";
	imageSeriesTemplate.fill = am4core.color("#000");
	imageSeriesTemplate.background.fillOpacity = 0;
	imageSeriesTemplate.background.fill = am4core.color("#ffffff");
	imageSeriesTemplate.setStateOnChildren = true;
	imageSeriesTemplate.states.create("hover");
	
	
	updateResults();
	updateStatus();
});
