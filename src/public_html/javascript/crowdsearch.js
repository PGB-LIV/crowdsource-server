


//JOHNS DEBUG MODERATOR
	var DEBUG_LEVEL_OFF = 4;
	var DEBUG_LEVEL_HIGH = 3; 		//only critical debug
	var DEBUG_LEVEL_MEDIUM = 2;		
	var DEBUG_LEVEL_LOW = 1;		//verbose debug -> console
	var DEBUG_LEVEL_ALL = 0
	
	var debugMinimumLevel = DEBUG_LEVEL_MEDIUM;	//anything below this priority doesn't get output (set it to HIGH if only critical debug information)
	function Debug(output , priority)	// = DEBUG_LEVEL_HIGH)
	{
		priority = (typeof priority !== 'undefined') ?  priority : DEBUG_LEVEL_HIGH;
		if	(priority >= debugMinimumLevel)
		{					
			console.log(output);
		}
	}
//END

	var PROVIDER_PHP = "http://pgb.liv.ac.uk/~johnheap/crowdsource-server/src/public_html/testscheduler.php";		//the php that handles data requests and accepts results (will need to go to master)
	var MAIN_INITIALISING = 0;
	var MAIN_AWAITING_SPECTRA = 1;	
	var MAIN_AWAITING_PROTEIN = 2;
	var MAIN_AWAITING_PROCESSING = 3;
	var MAIN_AWAITING_RESULTS_CONFIRMATION = 4;
	var MAIN_COMPLETE =5;
	
	
	
	var currentProtein = 0;	//1774;	//CLUDGE to cycle through proteins will be provided with correct protein by job taskmaster
	var myWorker;			//WebWorker instance		
	var debug="";			//only used to display stuff on this html 
	var mainStatus;		//where we are at.. main is currently event based so just used to keep track....
	var mySpectra;		//JSON from server as Spectrum object; 
	var myProtein;		//JASON from server as Protein object;
	
	
	var BuildWorker = function(foo){
	var str = foo.toString()
             .match(/^\s*function\s*\(\s*\)\s*\{(([\s\S](?!\}$))*[\s\S])/)[1];
	return  new Worker(window.URL.createObjectURL(
                      new Blob([str],{type:'text/javascript'})));
	}

	
	initialiseSearch();
	requestSpectrum();

	
	function initialiseSearch()
	{
		myWorker = BuildWorker(function()
		{
		
			var WORKER_NAME = "http://pgb.liv.ac.uk/~johnheap/crowdsource-server/src/public_html/javascript/cs_worker.js";			//name of worker 
			importScripts(WORKER_NAME);
		
		});
	}
		

		
	function requestSpectrum()
	{
		setMainStatus(MAIN_AWAITING_SPECTRA);
		$.getScript(PROVIDER_PHP+"?r=spectrum");
	}
	
	function receiveSpectra(json)
	{
		mySpectra = JSON.stringify(json);
		Debug("Spectra received = " +mySpectra,DEBUG_LEVEL_MEDIUM);		
		myWorker.postMessage(mySpectra);	//post mySpectra to worker;
	}
	
	function requestProtein()
	{
		setMainStatus(MAIN_AWAITING_PROTEIN);
		$.getScript(PROVIDER_PHP+"?r=protein&id="+currentProtein);
		currentProtein++;			//cludge... will not be necessary
	}
	function receiveProtein(json)
	{
		myProtein = JSON.stringify(json);
		Debug("Protein received = " +myProtein,DEBUG_LEVEL_MEDIUM);	
		myWorker.postMessage(myProtein);		//on receiving protein it will get to work	
	}
	
	function sendResult(resultString)
	{
		$.getScript(PROVIDER_PHP+"?r=result&"+resultString);
		var temp = JSON.parse(resultString)
		if (temp.peptides.length !=0)
		{
			Debug("Sending back "+resultString,DEBUG_LEVEL_HIGH);
			/*
			debug +="<br>"+resultString;
			document.getElementById("output").innerHTML = debug;
			*/
		}
	}
	function sendTerminating()
	{
		$.getScript(PROVIDER_PHP+"?r=terminate");
	}
	//this function is the wrapper padding of the JSONP response from server eg "parseResult(Object)"
	function parseResult(json)
	{
		switch (json.type)
		{
			case "spectrum":
				receiveSpectra(json);
				break;
			case "protein":
				receiveProtein(json);
				break;
			case "confirmation":
				Debug("Results Confirmation received",DEBUG_LEVEL_LOW);	
				requestProtein();
				break;
			case "no_more":		//no job to do.
				break;
			default:
				Debug("Unexpected Response "+json.type);
				break;
		}
	}

	myWorker.onmessage = function(e)		//worker communicates with the main js via JSON strings
		{
			Debug("Msg from Worker = "+e.data,DEBUG_LEVEL_LOW);
			
			var workerResponse = JSON.parse(e.data);
			
			switch (workerResponse.type)
			{
				case "acknowledge":
				switch (workerResponse.what)
				{
					case "spectrum":
						requestProtein();
						break;
					case "protein":
						setMainStatus(MAIN_AWAITING_PROCESSING);
						break;
					case "confirmation":		
						requestProtein();
						break;
				}
				break;
				
				case "result":
					sendResult(JSON.stringify(workerResponse));
					break;
			}
		}
/*
	function startWorker()
	{
		if (typeof(myWorker) == "undefined")
		{
			myWorker = new Worker(WORKER_NAME);
			
			if (typeof(myWorker) == "undefined")
			{
				Debug("worker NOT created");
			}
		}
		myWorker.onmessage = function(e)		//worker communicates with the main js via JSON strings
		{
			Debug("Msg from Worker = "+e.data,DEBUG_LEVEL_LOW);
			
			var workerResponse = JSON.parse(e.data);
			
			switch (workerResponse.type)
			{
				case "acknowledge":
				switch (workerResponse.what)
				{
					case "spectrum":
						requestProtein();
						break;
					case "protein":
						setMainStatus(MAIN_AWAITING_PROCESSING);
						break;
					case "confirmation":		
						requestProtein();
						break;
				}
				break;
				
				case "result":
					sendResult(JSON.stringify(workerResponse));
					break;
			}
		}
	}
*/
	function setMainStatus(newStatus)
	{
		Debug("MainStatus set to "+newStatus,DEBUG_LEVEL_LOW);
		mainStatus = newStatus;
	}
	//terminate session event. Also occurs on Refresh.
	$(window).on("beforeunload", function() {
		if (typeof(w) != "undefined")
		{
			Debug("Terminating Worker"); 
			myWorker.terminate();						//terminate the worker...
			sendTerminating();
		}
		//return "For debug purposes only";		//place this to see any debug (creates a pop up alert) comment out line for clean refresh/close
	});			
	

				
			
				
	
	


