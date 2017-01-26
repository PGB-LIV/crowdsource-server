


//JOHNS DEBUG MODERATOR
	var DEBUG_LEVEL_ALWAYS = 5;	//even if off (for debugging)
	var DEBUG_LEVEL_OFF = 4;
	var DEBUG_LEVEL_HIGH = 3; 		//only critical debug
	var DEBUG_LEVEL_MEDIUM = 2;		
	var DEBUG_LEVEL_LOW = 1;		//verbose debug -> console
	var DEBUG_LEVEL_ALL = 0
	
	var debugMinimumLevel = DEBUG_LEVEL_OFF;	//anything below this priority doesn't get output (set it to HIGH if only critical debug information)
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

	var myWorker;			//WebWorker instance		
	var myWorkUnit;
	
	var BuildWorker = function(foo){
	var str = foo.toString()
             .match(/^\s*function\s*\(\s*\)\s*\{(([\s\S](?!\}$))*[\s\S])/)[1];
	return  new Worker(window.URL.createObjectURL(
                      new Blob([str],{type:'text/javascript'})));
	}

	
	initialiseWorker();
	requestWorkUnit();
			
	function initialiseWorker()
	{
		myWorker = BuildWorker(function()
		{	
			var WORKER_NAME = "http://pgb.liv.ac.uk/~johnheap/crowdsource-server/src/public_html/javascript/cs_worker.js";			//name of worker will need to goto master
			importScripts(WORKER_NAME);
		});
	}
		
	function requestWorkUnit()
	{
		$.getScript(PROVIDER_PHP+"?r=workunit");
	}
	
	function receiveWorkUnit(json)
	{
		myWorkUnit = JSON.stringify(json);
		Debug("Work unit received = " +myWorkUnit,DEBUG_LEVEL_LOW);
		myWorker.postMessage(myWorkUnit);		//on receiving workUnit it will get to work	
	}
	
	
	function sendResult(resultString)
	{
		$.getScript(PROVIDER_PHP+"?r=result&result="+resultString);
	}
	
	function sendTerminating()
	{
		$.getScript(PROVIDER_PHP+"?r=terminate");
	}
	
	
	//this function is the P of the JSONP response from server eg "parseResult(Object)"
	function parseResult(json)
	{
		switch (json.type)
		{
			case "workunit":
				//Debug(JSON.stringify(json),DEBUG_LEVEL_ALWAYS);
				receiveWorkUnit(json);
				break;
				
			case "confirmation":
				Debug("Results Confirmation received",DEBUG_LEVEL_LOW);	
				requestWorkUnit();
				break;
			
			case "message":
				Debug("message ="+json.message,DEBUG_LEVEL_LOW);
				break;
			case "nomore":
				Debug("No more jobs found",DEBUG_LEVEL_ALWAYS);
				break;
			default:
				Debug("Unexpected Response "+json.type);
				break;
		}
		json=null;
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
				case "workunit": 
					break;
				case "confirmation":		
					break;
			}
			break;
			
			case "result":
				sendResult(JSON.stringify(workerResponse));
				break;
		}
	}

	
	//terminate session event. Also occurs on Refresh.
	$(window).on("beforeunload", function() {
		if (typeof(w) != "undefined")
		{
			Debug("Terminating Worker",DEBUG_LEVEL_ALWAYS); 
			myWorker.terminate();						//terminate the worker...
			sendTerminating();
		}
	});			
	

				
			
				
	
	


