
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/crowdsearch.css"/>
		<title> Crowd Search </title>
		
	</head>
	
	<body>

		<div id = "wrapper">
			<div id = "content">
				<h1>Crowd Search</h1>
  			
  			<div id = "formcontainer">
			
  				<form   enctype = "multipart/form-data" method ="post" action ="{$smarty.server.php_self}?page={$smarty.request.page}">
  					<fieldset>
  						<legend>Job Creation</legend>
  						<p>
							<label class = "field" for = "name">Name: </label>
							<input type = "text" name = "name" 
							value= {$name} 
							style="color:{$namecolor};"}>
						</p>
  						<p>
							<label class = "field" for = "email">Email: </label>
							<input type = "email" name = "email"
							value={$email} 
							style="color:{emailcolor};">					
						</p>
  						<p>
							<label class = "field" for = "jobname">Job Name: </label>
							<input type = "text" name = "jobname"
							value={$jobname) 
							style="color:{$jobcolor}";>
						</p>
  					
  						<p>
							<label class = "field" for = "enzyme">Enzyme:</label> 
  							<select name = "enzyme">
  								<option value = "trypsin">Trypsin</option>
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
							<label class = "field" for = "fastafile">Database: </label>
							<input type = "file" name="fastafile" id ="fastafile"
							value={$fastafile}
							style="color:{$fastacolor}>
						</p>
						
  					 	<p>
							<label class = "field" for = "rawfile">MS Data File: </label>
							<input type = "file" name ="rawfile" id="rawfile"
							value={$rawfile}
							style='color:{$rawcolor}">
							
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
			
						
  		</div>
  	</div>
	</body>
</html>






