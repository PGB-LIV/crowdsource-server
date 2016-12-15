<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/crowdsearch.css"/>
		<title> CrowdSearch </title>
		
	</head>
	
	<body>
		<div id = "wrapper">
			<div id = "content">
				<h1>CrowdSearch&#8482</h1>
			</div>
			<div style = "text-align:center;">
				<p> 
					<b>Name: {$name}
					<br>Email: {$email}</b>
				</p>
				
				<p>
					<b>Current Jobs:</b>
				</p>
			</div>
			{if $jobrecords[0]['job_title'] neq 'xx-nojob-xx'}
				<form method="post" action = "index.php?page=manage_job">
					<div id = "tablecontainer"  >
						<table style = "width:100%;">
							<tr>
								<th>Select</th>
								<th>Job title</th>
								<th>Date<br>Presented</th>
								<th>Spectra File</th>
								<th>FASTA file</th>
								<th>Enzyme</th>
								<th>Missed<br>Cleavage</th>
								<th>Charge<br>Range</th>
								<th>Tolerance</th>
								<th>Status</th>
						 	</tr>
					 
						 	{section name=job loop=$jobrecords}
						 	<tr>
						 		<td>
						 			<input type ="checkbox" name = "jobchecks[]" value = "{$jobrecords[job]['job_id']}">
						 		</td>
						 		<td>{$jobrecords[job]['job_title']}</td>
						 		<td>{$jobrecords[job]['job_time']}</td>
						 		<td>{$jobrecords[job]['database_file']}</td>
						 		<td>{$jobrecords[job]['raw_file']}</td>
						 		<td>{$jobrecords[job]['enzyme']}</td>
						 		<td>{$jobrecords[job]['miss_cleave_max']}</td>
						 		<td>{$jobrecords[job]['charge']}</td>
						 		<td>{$jobrecords[job]['tolerance']}</td>
						 		<td>{$jobrecords[job]['status']}</td>
						 	</tr>
						 	{/section}
						</table>
						<div>
						<p>
							<input type='submit' name = 'Pause' value = 'Pause' onclick="return confirm('Are you sure you want to pause or unpause the selected jobs?')"/>
							<input type='submit' name = 'Delete' value = 'Delete' onclick="return confirm('WARNING. Are you sure you want to delete the selected jobs?')"/>
							<button type="button" onclick="window.location.href='index.php?page=job_create'">Create New job</button>
						</p>
						<div>	
					</div>
				</form>
			</div>
			{else}
			<p style = "text-align:center;"> 
				No jobs currently scheduled
			</p>
			<div style = "text-align:center">
				<button type="button" onclick="window.location.href='index.php?page=job_create'">Create New job</button>
			</div>
			{/if}
		</div>	
		<script>
			function clickedajob()
			{
				alert("clicked a job");				
			}
		</script>
							
	<body>
</html>			
