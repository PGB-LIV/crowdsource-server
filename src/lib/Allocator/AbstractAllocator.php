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
use pgb_liv\crowdsource\Core\FragmentIon;
use pgb_liv\crowdsource\Core\Modification;

abstract class AbstractAllocator implements AllocatorInterface
{

    protected $adodb;

    protected $jobId;

    private $tableKeys;

    private $tableKeysWhere;

    private $tableName;

    private $phase;

    /**
     * Creates a new instance of a job allocator.
     *
     * @param \ADOConnection $conn
     *            A valid and connected ADOdb instance
     * @param int $jobId
     *            The job to preprocess
     * @throws \InvalidArgumentException If job is not an integer
     */
    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException(
                'Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    protected function setPhase($phase)
    {
        $this->phase = $phase;
        $this->tableName = 'workunit' . $phase;
    }

    protected function setWorkUnitKeys()
    {
        $this->tableKeys = func_get_args();
        $this->tableKeysWhere = '';
        for ($i = 0; $i < func_num_args(); $i ++) {
            if ($i > 0) {
                $this->tableKeysWhere .= ' && ';
            }
            
            $this->tableKeysWhere .= '`' . $this->tableKeys[$i] . '` = %s';
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \pgb_liv\crowdsource\Allocator\AllocatorInterface::setWorkUnitWorker()
     */
    protected function recordWorkUnitWorker($workerId)
    {
        if (! is_int($workerId)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an integer value. Valued passed is of type ' . gettype($workerId));
        }
        
        if (func_num_args() - 1 < count($this->tableKeys)) {
            $missingNum = func_num_args();
            
            throw new \BadMethodCallException(
                'Argument ' . $missingNum . ' must be specified. Expecting value for ' .
                     $this->tableKeys[$missingNum - 2]);
        }
        
        $keys = array();
        for ($i = 1; $i < func_num_args(); $i ++) {
            $keys[] = $this->adodb->Quote(func_get_arg($i));
        }
        
        $where = vsprintf($this->tableKeysWhere, $keys);
        
        $this->adodb->Execute(
            'UPDATE `' . $this->tableName . '` SET `status` = \'ASSIGNED\', `assigned_to` =' . $workerId . ', `assigned_at` = NOW()
        WHERE `job` = ' . $this->jobId . ' && ' . $where);
    }

    /**
     * Checks if the current phases work units are marked as COMPLETE.
     *
     * @return boolean Returns true if all work units are marked as complete, else false.
     */
    protected function isPhaseComplete()
    {
        $incompleteCount = $this->adodb->GetOne(
            'SELECT COUNT(`job`) FROM `workunit' . $this->phase . '` WHERE `status` != \'COMPLETE\'');
        
        return $incompleteCount == 0;
    }

    /**
     * Marks the job as complete for this phase
     */
    protected function setJobDone()
    {
        $this->adodb->Execute(
            'UPDATE `job_queue` SET `state` = \'DONE\' WHERE `id` = ' . $this->jobId .
                 ' && `state` = \'READY\' && phase = \'' . $this->phase . '\'');
    }

    protected function injectFixedModifications(WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `job_fixed_mod`.`mod_id`, `unimod_modifications`.`mono_mass`, `job_fixed_mod`.`acid` FROM `job_fixed_mod`
    INNER JOIN `unimod_modifications` ON `unimod_modifications`.`record_id` = `job_fixed_mod`.`mod_id` WHERE 
            `job_fixed_mod`.`job` = ' . $workUnit->getJobId());
        
        foreach ($rs as $record) {
            $modification = new Modification((int) $record['mod_id'], (float) $record['mono_mass'], 
                array(
                    $record['acid']
                ));
            $workUnit->addFixedModification($modification);
        }
    }

    /**
     * Injects the MS/MS data into the work unit object
     *
     * @param Phase1WorkUnit $workUnit
     *            Work unit to inject into
     *            
     */
    protected function injectFragmentIons(WorkUnit $workUnit)
    {
        $rs = $this->adodb->Execute(
            'SELECT `mz`, `intensity` FROM `raw_ms2` WHERE `job` = ' . $workUnit->getJobId() . ' && `ms1` = ' .
                 $workUnit->getPrecursorId());
        
        foreach ($rs as $record) {
            $workUnit->addFragmentIon(new FragmentIon((float) $record['mz'], (float) $record['intensity']));
        }
    }

    abstract public function getWorkUnit();

    abstract public function setWorkUnitResults(WorkUnit $workUnit);
}
