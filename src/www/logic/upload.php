<?php
if (isset($_FILES['rawFile']) && $_FILES['rawFile']['error'] == 0) {
    if (filesize($_FILES['rawFile']['tmp_name']) > 104857600) {
        echo 'MGF file too large';
        return;
    }

    $fields = array();
    $fields['database_file'] = DATA_PATH . '/databases/curated/' . $_POST['fasta'];
    $fields['raw_file'] = $_FILES['rawFile']['tmp_name'];
    $fields['enzyme'] = $_POST['enzyme'];
    $fields['miss_cleave_max'] = 3;
    $fields['charge_min'] = 1;
    $fields['charge_max'] = 255;
    $fields['peptide_min'] = 6;
    $fields['peptide_max'] = 60;

    $fields['precursor_tolerance'] = (int) $_POST['precursorTolerance'];
    $fields['precursor_tolerance_unit'] = 'ppm';

    $fields['fragment_tolerance'] = (int) $_POST['fragmentTolerance'];
    $fields['fragment_tolerance_unit'] = 'ppm';

    $table = 'job_queue';
    $sql = $adodb->getInsertSql($table, $fields);

    $adodb->Execute($sql);
    $jobId = $adodb->insert_id();

    $rawPath = DATA_PATH . '/' . $jobId;
    mkdir($rawPath);

    $rawPath .= '/' . $_FILES['rawFile']['name'];
    copy($_FILES['rawFile']['tmp_name'], $rawPath);

    $adodb->Execute('UPDATE `job_queue` SET `raw_file` = ' . $adodb->quote($rawPath) . ' WHERE `id` = ' . $jobId);

    foreach ($_POST['fixed'] as $fixedMod) {
        switch ($fixedMod) {
            case 'carb':
                $adodb->Execute('INSERT INTO `job_fixed_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 4, "C")');
                break;
        }
    }

    foreach ($_POST['variable'] as $variableMod) {
        switch ($variableMod) {
            case 'phospho':
                $adodb->Execute(
                    'INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 21, "S")');
                $adodb->Execute(
                    'INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 21, "T")');
                $adodb->Execute(
                    'INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 21, "Y")');
                break;
            case 'oxidation':
                $adodb->Execute(
                    'INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 35, "M")');
                break;
            case 'acetylation':
                $adodb->Execute(
                    'INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 1, "[")');
                break;
            case 'methylation':
                $adodb->Execute(
                    'INSERT INTO `job_variable_mod` (`job`, `mod_id`, `acid`) VALUES (' . $jobId . ', 34, "]")');
                break;
        }
    }

    header('Location: index.php?id=' . $jobId . '#download');
    exit();
}