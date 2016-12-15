<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/crowdsearch.css"/>
		<title> Crowd Search </title>
	</head>
	
	<body>
		<div id = "wrapper">
			<div id = "content">
				<h1>Welcome to CrowdSearch&#8482</h1>
				<h2>Sharing the load</h2>
				
				<p>
					 
				</p>
  			
				<div id = "formcontainer">
					<form method ="post" action ="index.php?page=login">
						<fieldset>
							<legend>Sign in</legend>
							<p>
								<label class = "field" for = "name">Name: </label>
								<input type = "text" name = "name" 
								value="{$name}" 
								>
							</p>
							<p>
								<label class = "field" for = "email">Email: </label>
								<input type = "email" name = "email" value="{$email}"> 
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
					<button type="button" {$shown} onclick="window.location.href='{$nextPage}'">{$nextString}</button>
				</div>
			</div>
		</div>
	</body>
</html>
		
	