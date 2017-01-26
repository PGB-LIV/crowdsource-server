<?php
require_once '../conf/config.php';

// require_once '../conf/autoload.php';

require_once '../lib/vendor/adodb/adodb-php/adodb.inc.php';

$adodb = newAdoConnection(DB_DRIVER);
$adodb->Connect(DB_HOST, DB_USER, DB_PASS, DB_DB);
$adodb->setFetchmode(ADODB_FETCH_ASSOC);



class WorkUnit
{
    public $type = "workunit";
    public $id;
    public $job;
    public $mods = array();
    public $ipAddress;
    public $ms1;
    public $ms2 = array();
    public $peptides = array();
}

$currentModsJob = -1;
$modsForCurrentJob =array();


$myWorkUnit = NULL; // {type:'workunit', id:0, job:0, mods:=[{modtype:0,modMass:0,loc:'C'}..],$ipAddress:0, ms1:0, ms2:=[{mz:n, intensity:n}...], peptides:[{id:1, structure:"ASDFFS"}...]}; 


    switch ($_GET['r'])
    {
        case "workunit":
            $myWorkUnit = requestNextWorkUnit();
            if ($myWorkUnit->job == 0)
            {
                echo 'parseResult({"type":"nomore"});';       //no more jobs this session.
                return;
            }
            if ($myWorkUnit->id == 0)
            {
                echo 'parseResult({"type":"nomore"});';       //no more jobs this session.
                return;
            }
            
            echo 'parseResult(' . json_encode($myWorkUnit) . ');';
            break;

        case "terminate":
            //client has successfully said goodbye. 
            break;
            
        case "result":
            //r=result&result={"type":"result","workunit":"1","job":30,"ip":0,"peptides":[{"id":784282,"score":262055.40000000002},...]}
            placeResult();
            echo 'parseResult({"type":"confirmation"})';
            break;
            
         default:
            echo 'parseResult({"response":"none"});';
      }
      
      
      //update database with scores for peptides.
      function placeResult()
      {
          global $adodb;
          //I have up to 5 scores to places
          $myResult=json_decode($_GET['result']);
          for ($i = 0; $i< count($myResult->peptides); $i++)
          {
           
            $score = $myResult->peptides[$i]->score;
            if ($score > 0)            //only place the score if > 0
            {
                $peptide = $myResult->peptides[$i]->id;
          
                $query = "UPDATE workunit_peptides SET score = '$score' 
                WHERE job = '$myResult->job' AND workunit = '$myResult->workunit' AND peptide = '$peptide'";
                $rs=$adodb->Execute($query);
            }
          }
          $query = "UPDATE workunit SET status = 'COMPLETE', completed_at = NOW() 
          WHERE id = '$myResult->workunit'";
          $rs=$adodb->Execute($query);   
      }
      

      
      
      function requestNextWorkUnit()
      {
          global $adodb;
          
          
          $_myWorkUnit = new WorkUnit();
          
                    
          //$query = "SELECT id FROM job_queue WHERE status = 'preprocessed'";
          $query = "SELECT id FROM job_queue WHERE state = 'READY' AND phase='1'";
          $rs = $adodb->GetRow($query);
          if (empty($rs))
          {
              //no more phase 1 one jobs;
               $_myWorkUnit->job=0;          //flag no work units available;
               return $_myWorkUnit;
          }
          
          $job = (int)$rs['id'];
          
          
          
          //create work unit and place defaults
         
          $_myWorkUnit->job = $job;
          $_myWorkUnit->ipAddress = ip2long($_SERVER['REMOTE_ADDR']);
         
         
          //Query table workUnit for job 30, status = unassigned
          $query = "SELECT id, ms1 FROM workunit WHERE job ='$job' AND status = 'UNASSIGNED'";
          $rs = $adodb->GetRow($query);
          if (empty($rs))
          {
                $query = "UPDATE job_queue SET state = 'DONE' WHERE id = '$job' AND phase = '1'";
                $rs= $adodb->Execute($query);
                $_myWorkUnit->id = 0;
                return;
          }
          
          $_myWorkUnit->id = (int)$rs['id'];
          $_myWorkUnit->ms1 = (int)$rs['ms1'];
            
          
          // {type:'workunit', id:0, job:0, mods:=[{modtype:'fixed',modMass:0,loc:'C'}..],$ipAddress:0, ms1:0, ms2:=[{mz:n, intensity:n}...], peptides:[{id:1, structure:"ASDFFS"}...]};
          if ($currentModsJob == $job)
          {
              $_myWorkUnit->mods= $modsForCurrentJob;
          }else{
              
                $_myWorkUnit->mods = requestModifications($job);
          }
          
          // get the ms2 arrary from spectrum ms1;
          $_myWorkUnit->ms2 = requestMs2($_myWorkUnit->job,$_myWorkUnit->ms1);
          
          
          // get the peptides array from workunit_peptides
          $_myWorkUnit->peptides = requestWUPeptides($_myWorkUnit->id,$_myWorkUnit->job); 
          
          
          $query = "UPDATE workunit SET status = 'ASSIGNED', assigned_to ='$_myWorkUnit->ipAddress', assigned_at = NOW()
          WHERE id = '$_myWorkUnit->id'";
          $rs = $adodb->Execute($query);
          
          return $_myWorkUnit;
      }
      
      
      
      
      function requestModifications($job)
      {
          global $adodb;
          global $currentModsJob;       //job that $modsForCurrentJob is appropriate
          global $modsForCurrentJob;
          
        
          
          if ($currentJob == $job)
          {
              return $modsForCurrentJob;
          }
          $modsForCurrentJob = Array();
          $currentJob = $job;    
          $query = "SELECT unimod_modifications.mono_mass, job_fixed_mod.acid FROM job_fixed_mod
          INNER JOIN unimod_modifications ON unimod_modifications.record_id = job_fixed_mod.mod_id
          WHERE job_fixed_mod.job = '$job'";
          
          //$query ="SELECT mod_id, acid FROM job_fixed_mod WHERE job = '$job'";
          $rs = $adodb->Execute($query);
          $i = 0;
          while (! $rs->EOF) {
              $modsForCurrentJob[$i]['modtype']="fixed";
              $modsForCurrentJob[$i]['modmass'] = (float)$rs->fields['mono_mass'];
              $modsForCurrentJob[$i]['loc'] = $rs->fields['acid'];
              $rs->MoveNext();
              $i ++;
          }
          return $modsForCurrentJob;
      }
      
      
      
      
      
      
      // get the peptides array from workunit_peptides
      function requestWUPeptides($wu,$job)
      {
          global $adodb;
          $_peps= array();
          $query = "SELECT fpeps.id, fpeps.peptide FROM fasta_peptides AS fpeps
          LEFT OUTER JOIN workunit_peptides AS wu_p ON wu_p.peptide=fpeps.id
          WHERE wu_p.job = '$job' AND wu_p.workunit='$wu'";
          $rs = $adodb->Execute($query);
          $i = 0;
          while (! $rs->EOF) {
              $_peps[$i]['id'] = (int)$rs->fields['id'];
              $_peps[$i]['structure'] = $rs->fields['peptide'];
              $rs->MoveNext();
              $i ++;
          }
          return $_peps;
      }
      
      // get the ms2 arrary from spectrum ms1;
      function requestMs2($job,$ms1)
      {
          global $adodb;
          $ms2=array();
          
          $query = "SELECT mz, intensity from raw_ms2 WHERE job = '$job' AND ms1 = '$ms1'";
          $rs = $adodb->Execute($query);
          $i = 0;
          while (! $rs->EOF) {
              $ms2[$i]['mz'] = (float)$rs->fields['mz'];
              $ms2[$i]['intensity'] = (float)$rs->fields['intensity'];
              $rs->MoveNext();
              $i ++;
          }
          return $ms2;
      }

 
?>