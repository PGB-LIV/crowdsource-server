function updateResults() {
	var jobId = $("#job_id").val();

	if (jobId == undefined || jobId == "") {
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

						if (data.status != "COMPLETE"
								&& data.status != "NOT_FOUND") {
							setTimeout(updateResults, 10000);
						}
					});
}

$(document).ready(function() {
	updateResults();
});
