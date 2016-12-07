<?php

class FastaParser extends Traversable
{

    private $filePath;

    private $fileHandle;
    
    private $fileLine;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function current()
    {
        // Value
    }

    public function key()
    {
        // Position
    }

    public function next()
    {
        // Parse next line
    }

    public function rewind()
    {
        // Reset file parsing to start
        if ($this->fileHandle != null) {
            fclose($this->fileHandle);
        }
        
        $this->fileHandle = fopen($this->filePathe, 'r');
    }

    public function valid()
    {
        // EOF?
    }

    /**
     * Gets the next line and increments the file iterator
     * @return The next line in the file
     */
    private function getLine()
    {
        $ret = null;
        if ($this->filePeek != null)
        {
            $ret = $this->filePeek;
            $this->filePeek = null;
        }
        else
        {
            $ret = fgets($this->fileHandle);
        }
        
        return $ret;
    }
    

    /**
     * Gets the next line, though does not move the file iterator
     * @return The next line in the file
     */
    private function peekLine()
    {
        if ($this->filePeek == null)
        {
            $this->fileLine = fgets($this->fileHandle);
        }
        
        return $this->filePeek;
    }
    
    private function parseValue()
    {
        $description = '';
        while ($line = fgets($this->fileHandle))
        {
            $line = trim($line);
            if (strpos($line, '>') !== 0)
            {
                continue;
            }
            $description = substr($line, 1);
            break;
        }
    }
}