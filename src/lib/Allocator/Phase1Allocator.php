<?php
/**
 * Copyright 2016 University of Liverpool
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace pgb_liv\crowdsource\Allocator;

use pgb_liv\crowdsource\Core\WorkUnit;

class Phase1Allocator implements AllocatorInterface
{

    private $currentModsJob = - 1;

    private $modsForCurrentJob = array();

    /**
     *
     * {@inheritdoc}
     *
     * @see \pgb_liv\crowdsource\Allocator\AllocatorInterface::getWorkUnit()
     */
    public function getWorkUnit()
    {
        $workUnit = new WorkUnit();
        
        $workUnit->job = $this->jobId;
        
        // Query table workUnit for job 30, status = unassigned
        $rs = $adodb->GetRow('SELECT `id`, `ms1` FROM `workunit` WHERE `job` =' . $this->jobId . ' AND `status` = \'UNASSIGNED\'');
        if (empty($rs)) {
            // Mark job as done
            $rs = $adodb->Execute('UPDATE `job_queue` SET `state` = \'DONE\' WHERE `id` = ' . $this->jobId . ' AND phase = \'1\'');
            
            return null;
        }
        
        $workUnit->id = (int) $rs['id'];
        $workUnit->ms1 = (int) $rs['ms1'];
        
        // {type:'workunit', id:0, job:0, mods:=[{modtype:'fixed',modMass:0,loc:'C'}..],$ipAddress:0, ms1:0, ms2:=[{mz:n, intensity:n}...], peptides:[{id:1, structure:"ASDFFS"}...]};
        if ($currentModsJob == $this->jobId) {
            $workUnit->mods = $modsForCurrentJob;
        } else {
            
            $workUnit->mods = $this->getFixedModifications();
        }
        
        // get the ms2 arrary from spectrum ms1;
        $workUnit->ms2 = $this->getMs2($workUnit->job, $workUnit->ms1);
        
        // get the peptides array from workunit_peptides
        $_myWorkUnit->peptides = requestWUPeptides($workUnit->id, $workUnit->job);
    }

    private function getFixedModifications()
    {
        if ($currentJob == $this->jobId) {
            return $modsForCurrentJob;
        }
        
        $this->modsForCurrentJob = Array();
        $currentJob = $this->jobId;
        
        // $query ="SELECT mod_id, acid FROM job_fixed_mod WHERE job = '$job'";
        $rs = $adodb->Execute(
            'SELECT `unimod_modifications`.`mono_mass`, `job_fixed_mod`.`acid` FROM `job_fixed_mod`
    INNER JOIN `unimod_modifications` ON `unimod_modifications`.`record_id` = `job_fixed_mod`.`mod_id` WHERE 
            `job_fixed_mod`.`job` = ' . $this->jobId);
        
        $i = 0;
        while (! $rs->EOF) {
            $this->modsForCurrentJob[$i]['modtype'] = 'fixed';
            $this->modsForCurrentJob[$i]['modmass'] = (float) $rs->fields['mono_mass'];
            $this->modsForCurrentJob[$i]['loc'] = $rs->fields['acid'];
            $rs->MoveNext();
            $i ++;
        }
        
        return $this->modsForCurrentJob;
    }
    
    // get the ms2 arrary from spectrum ms1;
    function getMs2($ms1)
    {
        // TODO: Validate $ms1
        $ms2 = array();
        
        $rs = $adodb->Execute('SELECT `mz`, `intensity` FROM `raw_ms2` WHERE `job` = ' . $this->jobId . ' && `ms1` = ' . $ms1);
        $i = 0;
        while (! $rs->EOF) {
            $ms2[$i]['mz'] = (float) $rs->fields['mz'];
            $ms2[$i]['intensity'] = (float) $rs->fields['intensity'];
            $rs->MoveNext();
            $i ++;
        }
        
        return $ms2;
    }
}