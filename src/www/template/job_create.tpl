
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/crowdsearch.css"/>
		<title> Crowd Search </title>
		
	</head>
	
	<body>
		<div id = "wrapper">
			<div id = "content">
				<h1>CrowdSearch&#8482</h1>
  				<div id = "formcontainer">
			
	  				<form   enctype = "multipart/form-data" method ="post" action ="index.php?page=job_create">
	  					<fieldset>
	  						<legend>Job Creation</legend>
	  						<p>
								<label class = "field" for = "name">Name: </label>
								<input type = "text" name = "name" value= "{$name}" disabled >
							</p>
	  						<p>
								<label class = "field" for = "email">Email: </label>
								<input type = "email" name = "email" value="{$email}" disabled >					
							</p>
	  						<p>
								<label class = "field" for = "jobname">Job Name: </label>
								<input type = "text" name = "jobname" value="{$jobname}" required >
							</p>
	  						<p>
								<label class = "field" for = "enzyme">Enzyme:</label> 
	  							<select name = "enzyme">
	  								<option value = "1">Trypsin</option>
	  							</select>
	  						</p>
	  						<p>
	  							<label class = "field" for = "missedCleave">Missed Cleavages:</label> 
	  							<select name = "missedCleave">
	  								<option value = "1"> 1</option>
	  								<option value = "2"> 2</option>
	  								<option value = "3"> 3</option>
	  							</select>
	  						</p>
	  						<p>
	  							<label class = "field" for = "charge">Peptide Charge:</label> 
	  							<select name = "charge">
	  								<option value = "1"> +1</option>
	  								<option value = "2"> +2</option>
	  								<option value = "3"> +3</option>
	  							</select>
	  						</p>
	  						<p>
	  							<label class = "field" for = "tolerance">Error Tolerance: ppm </label> 
	  							<input type ="number" name = "tolerance" value='10' style="width:30%" >	
	  						</p>
	  						<p>
	  							<label class = "field" for = "fastasel">Database:</label> 
	  							<select id = "fastasel" name = "fastasel" onChange="checkCustom()">
	  							{section name=fasta loop = $fastaArray}
	  								<option value = "{$fastaArray[fasta]}">{$fastaArray[fasta]}</option>						
	  							{/section}
	  								<option value = "custom">Custom Database</option>
	  							</select>
	  						</p>
	  						<p id = "choosefasta" style="visibility:hidden">
	  							<label class = "field" for = "customfasta" disabled >Custom Database:</label>
	  							<input type = "file" id = "customfasta" name="customfasta" id ="customfasta" value="{$fastafile}">
	  						</p>
	  					 	<p>
								<label class = "field" for = "rawfile">MS Data File: </label>
								<input type = "file" name ="rawfile" id="rawfile" value="{$rawfile}" required>
							</p>
							<p>
	  							<span style="text-align:center">
	  								<input type="submit" value = "Submit" style = "width:30%;">
	  							</span>
	  						</p>
	  					</fieldset>
	  				<form>
	  			</div>
				<p>
					{$procString}
				</p>
				<div>
						<button type="button" {$shown} onclick="window.location.href='index.php?page=manage_job'">View Jobs</button>
				</div>		
	  		</div>
  	
  	
	  		<script>
	  			function checkCustom()
	  			{
		  			var e = document.getElementById("fastasel").value;
					var s = document.getElementById("choosefasta");
					var i = document.getElementById("customfasta");
					if (e == 'custom'){
						s.style.visibility="visible";
						i.required = true;
					}else{
						s.style.visibility="hidden";
						i.required=false;
					}
				}
			</script>
		</div>
  	
	</body>
</html>






