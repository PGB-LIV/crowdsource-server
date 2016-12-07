<?php

class FastaParser implements Iterator
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
        $description = '';
        while ($line = $this->getLine()) {
            $line = trim($line);
            if (strpos($line, '>') !== 0) {
                continue;
            }
            
            $description = substr($line, 1);
            break;
        }
        
        $sequence = '';
        while ($line = $this->peekLine()) {
            $line = trim($line);
            
            if (strpos($line, '>') === 0) {
                break;
            }
            
            $sequence .= trim($this->getLine());
        }
        
        $entry = array();
        $entry['description'] = $description;
        $entry['sequence'] = $sequence;
        
        $this->key++;
        
        return $entry;
    }
}