<?php
/**
 * Copyright 2018 University of Liverpool
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

    private $bufferLength = - 1;

    private $adodb;

    private $prefix = null;

    private $query;

    private $appendCount = 0;

    private $autoExec = true;

    private $execRequired = false;

    /**
     * Creates a new instance with the specified parser as input
     *
     * @param ADOdbConnection $conn
     *            ADOdb connection to use for queries
     * @param string $prefix
     *            Initial query prefix that will initiate each bulk query
     */
    public function __construct(\ADOConnection $conn, $prefix, $autoExec = true)
    {
        $this->adodb = $conn;
        $this->prefix = $prefix;
        $this->autoExec = $autoExec;

        // Auto-detect buffer length
        $this->bufferLength = $this->adodb->getRow('SHOW VARIABLES LIKE \'max_allowed_packet\';');
        $this->bufferLength = $this->bufferLength['Value'] - ((int) ($this->bufferLength['Value'] / 10));
    }

    /**
     * Sets the size of the buffer to store in memory before pushing to the database
     *
     * @param int $bufferLength
     *            The size of the buffer in bytes
     */
    public function setBufferLength($bufferLength)
    {
        $this->bufferLength = $bufferLength;
    }

    /**
     * Gets the current buffer length
     *
     * @return int
     */
    public function getBufferLength()
    {
        return $this->bufferLength;
    }

    private function execute()
    {
        $this->adodb->execute($this->query);
        $this->appendCount = 0;
        $this->query = null;
        $this->execRequired = false;
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

        if (strlen($this->query) > $this->bufferLength) {
            $this->execRequired = true;
        }

        if ($this->autoExec && $this->execRequired) {
            $this->execute();
        }
    }

    public function flush()
    {
        if (! is_null($this->query)) {
            $this->execute();
        }
    }

    public function close()
    {
        $this->flush();
    }

    public function isExecRequired()
    {
        return $this->execRequired;
    }
}
