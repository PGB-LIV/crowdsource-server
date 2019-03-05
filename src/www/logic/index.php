<?php
$rawFastaFiles = scandir(DATA_PATH . '/databases/curated');

$fastaFiles = array();
foreach ($rawFastaFiles as $fastaFile) {
    if ($fastaFile == '.' || $fastaFile == '..') {
        continue;
    }
    
    $fastaFiles[] = $fastaFile;
}

$smarty->assign('fastaFiles', $fastaFiles);