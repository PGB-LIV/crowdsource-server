
//JOHNS DEBUG MODERATOR
	var DEBUG_LEVEL_ALWAYS = 4;
	var DEBUG_LEVEL_OFF = 3;
	var DEBUG_LEVEL_HIGH = 2; 
	var DEBUG_LEVEL_MEDIUM = 1;
	var DEBUG_LEVEL_LOW = 0;
	
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

	
	var MAX_NUMBER_SCORES_RETURNED = 5;			//only retunr the top 5 peptide scores for this workunit
	
	
	
	
	
	
	
	var WORKER_INITIALISING = 0;
	var WORKER_AWAITING_WORKUNIT = 1;	
	var WORKER_PROCESSING = 3;
	var WORKER_AWAITING_CONFIRMATION = 4;
	var WORKER_COMPLETE = 5;
	
	var myWorkUnit; // {type:'workunit', id:0, job:0, $ipAddress:0, ms1:0, ms2:[{mz:n, intensity:n}...], peptides:[{id:1, structure:"ASDFFS"}...]}; 
	var workerStatus = WORKER_AWAITING_WORKUNIT;
	
	
	onmessage = function (event){
		var myObj = JSON.parse(event.data);
		var returnObj = null;
		var retString = "";
		switch (myObj.type)
		{
			case "workunit":
				retString = '{"type":"acknowledge","what":"workunit"}';
				myWorkUnit=myObj;
				setStatus(WORKER_PROCESSING);
				break;
				
			case "confirmation":
				retString = '{"type":"acknowledge","what":"confirmation"}';	
				//setStatus(WORKER_AWAITING_PROTEIN);
				break;
			
			default:
				retString = '{"type":"acknowledge","what":"message"}';	
				break;
		}
		postMessage(retString);
		if (workerStatus == WORKER_PROCESSING)
		{
			doMS2Search();
			setStatus(WORKER_COMPLETE);
		}
	}
	
	//it may be useful to normalise ms2 to 0 - 100 where 100 = maximum intensity.
	function normaliseMs2()
	{
		//warning I actually change mySpectrum!!!
		var _maxIntensity = 0;
		for (i = 0; i < myWorkUnit.ms2.length; i++)
		{
			if (myWorkUnit.ms2[i].intensity > _maxIntensity)
			{
				_maxIntensity = myWorkUnit.ms2[i].intensity;
			}
		}
		
		for (i = 0; i < myWorkUnit.ms2.length; i++)
		{
			s = myWorkUnit.ms2[i].intensity;
			s *= 100/_maxIntensity;
			//s = s.toFixed(4);
			//s *=1	//recast it to number (sometimes get null scores otherwise!!)
			myWorkUnit.ms2[i].intensity = s;
		}		
	}

	function setStatus(newStatus)
	{
		Debug("Worker Status set to " + newStatus,DEBUG_LEVEL_LOW);
		workerStatus = newStatus;
	}
	 
	
	// {type:'workunit', id:0, job:0, mods:=[{modtype:'fixed',modMass:0,loc:'C'}..],$ipAddress:0, ms1:0, ms2:=[{mz:n, intensity:n}...], peptides:[{id:1, structure:"ASDFFS"}...]}; 
	function doMS2Search()
	{
		var resultObject = {type:"result",workunit:0,job:0,ip:0,peptides:[] };		//array of best 5 Pepdtide [{id score}]
		
			
		var allPeptideScores=[];		//array of id, final score
		
		normaliseMs2();					//make ms2 intensities between 0 - 100
		
				
			//lets set up the current default resultObject properties that we know.
		resultObject.job = myWorkUnit.job;
		resultObject.workunit = myWorkUnit.id;
		Debug("number of peptides="+myWorkUnit.peptides.length,DEBUG_LEVEL_ALWAYS);
		for (p = 0; p < myWorkUnit.peptides.length; p++)
			{
				var scoreObj = {id:0, score:0};
				bscore = 0;
				yscore = 0;
				bcount = 0;
				ycount = 0;
				
				fragment(myWorkUnit.peptides[p].structure);			//flush out myB_ions & myY_ions array.
				
				Debug("B ions = "+myB_ions,DEBUG_LEVEL_LOW);
				Debug("Y ions = "+myY_ions,DEBUG_LEVEL_LOW);
				
				
				//score+ = intensity of a match (+-0.2Daltons)
				//check B ions against ms2 records;
				
				for (b = 0; b < myB_ions.length; b++)
				{
					for (m = 0; m < myWorkUnit.ms2.length; m++)
					{
						if (Math.abs(myB_ions[b]-myWorkUnit.ms2[m]['mz']) <= 0.2)
						{
							Debug("B ion match",DEBUG_LEVEL_LOW);
							bcount++;
							bscore += myWorkUnit.ms2[m]['intensity'];		
						}
					}
				}
												//check Y ions against ms2 records;								
				for (y = 0; y < myY_ions.length; y++)
				{
					for (m = 0; m < myWorkUnit.ms2.length; m++)
					{
						if (Math.abs(myY_ions[y]-myWorkUnit.ms2[m]['mz']) <= 0.2)
						{
							Debug("Y ion match",DEBUG_LEVEL_LOW);
							ycount++;
							yscore += myWorkUnit.ms2[m]['intensity'];		
						}
					}
				}
				Debug("bscore,yscore = "+bscore+","+yscore, DEBUG_LEVEL_LOW);
				
				//important bit.... 				//score = (bscore+yscore)*(bcount+ycount);		//we will need to investigate the best scoring formula 
				scoreObj.score = (bscore+yscore)*(bcount)*(ycount);
				scoreObj.id = myWorkUnit.peptides[p].id;
				allPeptideScores.push(scoreObj);
			}
	
		//Only wish to return the MAX_NUMBER_SCORES_RETURNED	
		//Order array by score 
			
			allPeptideScores.sort(function(a,b){return b.score - a.score});
			var numRes = MAX_NUMBER_SCORES_RETURNED;
			if (myWorkUnit.peptides.length < numRes)
			{
				numRes = myWorkUnit.peptides.length;
			}
			
			for (r = 0; r < numRes; r++)
			{
				resultObject.peptides.push(allPeptideScores[r])
			}
			var retString = JSON.stringify(resultObject);
			Debug("retString="+retString,DEBUG_LEVEL_ALWAYS);
			postMessage(retString);
	}
	
			
	
			
	var AAMass = {A:71.037114,
				R:156.101111,
				N:114.042927,
				D:115.026943,
				C:103.009185,
				E:129.042593,
				Q:128.058578,
				G:57.021464,
				H:137.058912,
				I:113.084064,
				L:113.084064,
				K:128.094963,
				M:131.040485,
				F:147.068414,
				P:97.052764,
				S:87.032028,
				T:101.047679,
				U:150.95363,
				W:186.079313,
				Y:163.063329,
				V:99.068414,
				X:0
				};
				
	var myB_ions = [];		//array of B ions filled by fragment() upon successful ms1 
	var myY_ions = [];		//array of Y ions filled by fragment() upon successful ms1
	//creates arrays of B and Y ions in myB and myY of peptide[index] held in myProtein;
	function fragment(struc)				 
	{
		var myPeptide = struc;		//myProtein.peptides[index]['structure'];
		Debug("Fragmenting "+myPeptide, DEBUG_LEVEL_LOW);
		var cm = 0;
		myB_ions = [];
		for (i = 0; i < myPeptide.length; i++)
		{
			var aa = myPeptide.charAt(i);
			modmass = checkforPTM(aa,'b');
			var m = AAMass[aa]; 
			if (i == 0){	//first time through? -0H + water
				m += 1.007276;		//add a hydrogen
			}
			m +=modmass;
			cm+=m;
			myB_ions[i]= cm.toFixed(6);
		}
		cm = 0;
		myY_ions=[];
		for (i = myPeptide.length-1; i >= 0; i--)
		{
			var aa = myPeptide.charAt(i);
			modmass = checkforPTM(aa,'y');
			var m = AAMass[aa]; 
			if (i == (myPeptide.length-1)){	//first time through? H + water
				m +=  18.010565+1.007276;		
			}
			m+=modmass;
			cm+=m;
			myY_ions[i]= cm.toFixed(6);
		}
	}
	

	function checkforPTM(aa,ion)
	{
		var masstoadd = 0;
		for (m = 0; m < myWorkUnit.mods.length; m++)
		{
			if (myWorkUnit.mods[m]['loc']==aa)
			{
				masstoadd+=myWorkUnit.mods[m]['modmass'];
				//Debug("mass added ="+masstoadd,DEBUG_LEVEL_ALWAYS)
			}
		}
		return masstoadd;
	}
		
		
		
		
		
		
		
		
	
		
		