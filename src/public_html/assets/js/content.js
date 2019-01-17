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
					"http://pgb.liv.ac.uk/~andrew/crowdsource-server/src/public_html/index.php?id="
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
							content += "<p>Your search is currently queued while another search is being complete.<br />";
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
					"http://pgb.liv.ac.uk/~andrew/crowdsource-server/src/public_html/index.php?page=json_monitor_status",
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

						content += '<table><thead><tr><th>ID</th><th>Scans</th><th>Duration</th><th>Bandwidth (GB)</th></tr></thead><tbody>';

						for (var i = 0; i < data.last_jobs.length; i++) {
							var duration = data.last_jobs[i].duration;

							var ms = duration % 1000;
							duration = Math.floor(duration / 1000);

							var hours = Math.floor(duration / 3600);
							duration = duration - (hours * 3600);

							var minutes = Math.floor(duration / 60);
							duration = duration - (minutes * 60);

							hours = hours + "";
							minutes = minutes + "";
							var seconds = duration + "";

							content += '<tr>';
							content += '<td>' + data.last_jobs[i].id + '</td>';
							content += '<td>'
									+ data.last_jobs[i].precursors
									+ '</td>';
							content += '<td>' + hours.padStart(2, '0') + ':' + minutes.padStart(2, '0') + ':' + seconds.padStart(2, '0') + '.' + ms + '</td>';
							content += '<td>' + Math.round((((data.last_jobs[i].data / 1024) / 1024) /1024) * 100) / 100
									+ '</td>';
							content += '</tr>';
						}

						content += '</tbody></table>';

						var today = new Date();
						content += '<p style="text-align: right; font-size: 50%">As at '
								+ today.toLocaleString("en-gb") + '</p>';

						$(".status_here").empty();
						$(".status_here").append(content);

						setTimeout(updateStatus, 15000);
					});
}

$(document).ready(function() {
	updateResults();
	updateStatus();
});
