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
namespace pgb_liv\crowdsource;

/**
 *
 * @author Andrew Collins
 */
class BulkQuery
{

    private $maxPacketSize = 1024;

    private $adodb;

    private $prefix = null;

    private $query;

    private $appendCount = 0;

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn
     *            ADOdb connection to use for queries
     * @param string $prefix
     *            Initial query prefix that will initiate each bulk query
     */
    public function __construct(\ADOConnection $conn, $prefix)
    {
        $this->adodb = $conn;
        $this->prefix = $prefix;
        $this->setMaxPacketLimit();
    }

    /**
     * Retrieves max packet size from the database for use in bulk process limits
     */
    private function setMaxPacketLimit()
    {
        $this->maxPacketSize = $this->adodb->getRow('SHOW VARIABLES LIKE \'max_allowed_packet\';');
        $this->maxPacketSize = $this->maxPacketSize['Value'] - ((int) ($this->maxPacketSize['Value'] / 10));
    }

    private function execute()
    {
        $this->adodb->execute($this->query);
        $this->appendCount = 0;
        $this->query = null;
    }

    public function append($query)
    {
        if (is_null($this->query)) {
            $this->query = $this->prefix;
        }
        
        if ($this->appendCount > 0) {
            $this->query .= ',';
        }
        
        $this->query .= $query;
        $this->appendCount ++;
        
        if (strlen($this->query) > $this->maxPacketSize) {
            $this->execute();
        }
    }

    public function close()
    {
        if (! is_null($this->query)) {
            $this->execute();
        }
    }
}
