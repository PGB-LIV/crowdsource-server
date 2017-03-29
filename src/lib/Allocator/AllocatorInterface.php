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

interface AllocatorInterface
{

    /**
     * Gets the next available work unit for the current job.
     *
     * @param int $workerId
     *            Worker ID to assign to work unit
     *            
     * @return pgb_liv\crowdsource\Core\WorkUnitInterface The next available job or null if no jobs available.
     */
    public function getWorkUnit($workerId);

    /**
     * Records the results for this work unit
     *
     * @param Phase1WorkUnit $results
     *            Work unit object containing results data
     */
    public function setWorkUnitResults(WorkUnit $results);
}
