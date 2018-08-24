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

if (isset($_FILES['mgf']) && $_FILES['mgf']['error'] == 0) {
    if (filesize($_FILES['mgf']['tmp_name']) > 104857600) {
        echo 'MGF file too large';
        return;
    }

    $fields = array();
    $fields['database_file'] = DATA_PATH . '/databases/curated/' . $_POST['fasta'];
    $fields['raw_file'] = $_FILES['mgf']['tmp_name'];
    $fields['enzyme'] = $_POST['enzyme'];
    $fields['miss_cleave_max'] = 3;
    $fields['charge_min'] = 1;
    $fields['charge_max'] = 255;
    $fields['peptide_min'] = 6;
    $fields['peptide_max'] = 60;

    $tolerance = explode(' ', $_POST['precursorTolerance']);

    $fields['precursor_tolerance'] = $tolerance[0];
    $fields['precursor_tolerance_unit'] = $tolerance[1];

    $tolerance = explode(' ', $_POST['fragmentTolerance']);
    $fields['fragment_tolerance'] = $tolerance[0];
    $fields['fragment_tolerance_unit'] = $tolerance[1];

    $table = 'job_queue';
    $sql = $adodb->getInsertSql($table, $fields);

    $adodb->Execute($sql);
    $jobId = $adodb->insert_id();

    $rawPath = DATA_PATH . '/' . $jobId;
    mkdir($rawPath);

    $rawPath .= '/' . $_FILES['mgf']['name'];
    copy($_FILES['mgf']['tmp_name'], $rawPath);

    $adodb->Execute('UPDATE `job_queue` SET `raw_file` = ' . $adodb->quote($rawPath) . ' WHERE `id` = ' . $jobId);

    if (isset($_POST['fixed']) && $_POST['fixed'] == 'Yes') {
        $adodb->Execute('INSERT INTO `job_fixed_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 4, "C")');
    }

    if (isset($_POST['variable']) && $_POST['variable'] == 'phospho') {
        $adodb->Execute('INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 21, "S")');
        $adodb->Execute('INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 21, "T")');
        $adodb->Execute('INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 21, "Y")');
    }

    $adodb->Execute(
        'UPDATE `job_queue` SET `raw_file` = ' . $adodb->quote($rawPath) . ', `state` = "DONE" WHERE `id` = ' . $jobId);

    header('Location: index.php?page=monitor&job=' . $jobId);
    exit();
}