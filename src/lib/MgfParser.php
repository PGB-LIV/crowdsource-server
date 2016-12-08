<?php

class MgfParser implements Iterator
{

    private $filePath;

    private $fileHandle;

    private $filePeek;

    private $current;

    private $key = 0;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function current()
    {
        return $this->current;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->current = null;
        if (! feof($this->fileHandle)) {
            $this->current = $this->parseEntry();
        }
    }

    public function rewind()
    {
        // Reset file parsing to start
        if ($this->fileHandle != null) {
            fclose($this->fileHandle);
        }
        
        $this->fileHandle = fopen($this->filePath, 'r');
        $this->key = 0;
        $this->current = $this->parseEntry();
    }

    public function valid()
    {
        if (is_array($this->current)) {
            return true;
        }
        
        return false;
    }

    /**
     * Gets the next line and increments the file iterator
     *
     * @return The next line in the file
     */
    private function getLine()
    {
        $ret = null;
        if ($this->filePeek != null) {
            $ret = $this->filePeek;
            $this->filePeek = null;
        } else {
            $ret = fgets($this->fileHandle);
        }
        
        return $ret;
    }

    /**
     * Gets the next line, though does not move the file iterator
     *
     * @return The next line in the file
     */
    private function peekLine()
    {
        if ($this->filePeek == null) {
            $this->filePeek = fgets($this->fileHandle);
        }
        
        return $this->filePeek;
    }

    private function parseEntry()
    {
        $entry = array();
        
        // Scan to BEGIN IONS
        $isFound = false;
        while ($line = $this->getLine()) {
            $line = trim($line);
            if (strpos($line, 'BEGIN IONS') !== 0) {
                continue;
            }

            $isFound = true;
            break;
        }
        
        if (!$isFound)
        {
            return null;
        }
        
        // Scan for key=value pairs
        $entry['meta'] = array();
        while ($line = $this->peekLine()) {
            if (strpos($line, '=') === false) {
                break;
            }
            
            $line = trim($this->getLine());
            $pair = explode('=', $line, 2);
            
            $entry['meta'][$pair[0]] = $pair[1];
        }
        
        // Scan for [m/z] [intensity]
        $entry['ions'] = array();
        while ($line = $this->peekLine()) {
            if (strpos($line, 'END IONS') !== false) {
                break;
            }
            
            $line = trim($this->getLine());
            $pair = explode(' ', $line, 2);
            
            $ion = array();
            $ion['mz'] = $pair[0];
            if (count($pair) > 1) {
                $ion['intensity'] = $pair[1];
            }
            
            if (count($pair) > 2) {
                $ion['charge'] = $pair[2];
            }
            
            $entry['ions'][] = $ion;
        }
        
        $this->key ++;
        
        return $entry;
    }
}
