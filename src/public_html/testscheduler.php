<?php
require_once '../conf/config.php';

// require_once '../conf/autoload.php';

require_once '../lib/vendor/adodb/adodb-php/adodb.inc.php';

$adodb = newAdoConnection(DB_DRIVER);
$adodb->Connect(DB_HOST, DB_USER, DB_PASS, DB_DB);
$adodb->setFetchmode(ADODB_FETCH_ASSOC);

class Spectrum
{
    public $type = "spectrum";
    public $job;
    public $ms1;
    public $pepmass;
    public $charge;
    public $rtime;
    public $ms2 = array();
};


class Protein
{
    public $type = "protein";
    public $job;
    public $protein;
    public $peptides = array();
};

$mySpectrum = NULL;  // {"type":"spectrum","job":n,ms1:n,pepmass:n,charge:n, ms2:[{mz:n,intensity:n}]}
$myProtein = NULL;   // {"type":"protein","job":30,"protein":"1","peptides":[{"id":1,"structure":"ASLIQK","mc":1,"mass":658.401367},...]}


    switch ($_GET['r'])
    {
        case "spectrum":
            $mySpectrum = requestNextSpectrum();  // returns {"type":"spectrum","job":n,ms1:n,pepmass:n,charge:n, ms2:[{mz:n,intensity:n}]}
            echo 'parseResult(' . json_encode($mySpectrum) . ');';
            break;
        case "protein":
            $myProtein = requestNextProtein($_GET['id']);           //($_get['id'] is a CLUDGE till job scheduling is done
            echo 'parseResult(' . json_encode($myProtein) . ');';
            break;
        case "terminate":
            //client has successfully said goodbye. 
            break;
        case "result":
            //we have a positive result for the c 
            //?r=result&result='{"type":"result","job":30,"ms1":1,"protein":"1","score":0,"peptides":[{"id":0,"score":0,"b_count":0,"y_count":0},,,]}'
            //for now I'll just send a confirmation;
            echo 'parseResult({"type":"confirmation"})';
            break;
         default:
            echo 'parseResult({"response":"none"});';
      }
                



function requestNextProtein($prot)          //$prot is a CLUDGE final schedule will give me the correct protein  
{
    
    // will need a proper scheduler but lets build one for now.
    global $adodb;
    $protein = $prot; // id of protein to search for.... CLUDGE will be scheduled by database
    $job = 30; // only got job 30;
    $_protein = new Protein();
    $_protein->protein = $protein;
    $_protein->job = $job;
    
    $query = "SELECT fpeps.id, fpeps.peptide, fpeps.missed_cleavage, fpeps.mass FROM fasta_peptides AS fpeps 
	    LEFT OUTER JOIN fasta_protein2peptide AS fp2p ON fp2p.peptide=fpeps.id
	    WHERE fp2p.job = '$_protein->job' AND fp2p.protein='$_protein->protein'";
    
    $rs = $adodb->Execute($query);
 
    $i = 0;
    while (! $rs->EOF) {
        $_protein->peptides[$i]['id'] = (int)$rs->fields['id'];
        $_protein->peptides[$i]['structure'] = $rs->fields['peptide'];
        $_protein->peptides[$i]['mc'] = (int)$rs->fields['missed_cleavage'];
        $_protein->peptides[$i]['mass'] = (float)$rs->fields['mass'];
        $rs->MoveNext();
        $i ++;
    }
    return $_protein;
}

function requestNextSpectrum()
{
    // will need a proper scheduler...... but lets just build the first one.
    // asks for a job and ms1 to deal with.
    // for now
    global $adodb;
    
    $job = 30;
    $ms1 = 1;
    $_spectrum = new Spectrum();
    $_spectrum->job = $job;
    $_spectrum->ms1 = $ms1;
    
    $query = "SELECT * FROM raw_ms1 WHERE job = '$_spectrum->job' AND id = '$_spectrum->ms1' LIMIT 1";
    
    $rs = $adodb->Execute($query); // should only get one!
    
    $_spectrum->pepmass = (float)$rs->fields['pepmass'];
    $_spectrum->charge = (int)$rs->fields['charge'];
    $_spectrum->rtime = (float)$rs->fields['rtinseconds'];
    
    $query = "SELECT * from raw_ms2 WHERE job = '$_spectrum->job' AND ms1 = '$_spectrum->ms1' LIMIT 50";
    $rs = $adodb->Execute($query);
    $i = 0;
    while (! $rs->EOF) {
        $_spectrum->ms2[$i]['mz'] = (float)$rs->fields['mz'];
        $_spectrum->ms2[$i]['intensity'] = (float)$rs->fields['intensity'];
        $rs->MoveNext();
        $i ++;
    }
    return $_spectrum;
}

?>