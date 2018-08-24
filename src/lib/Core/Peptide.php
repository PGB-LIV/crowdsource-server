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
namespace pgb_liv\crowdsource\Core;

use pgb_liv\php_ms\Core\Peptide as BasePeptide;

/**
 * Class to encapsulate peptide properties
 *
 * @author Andrew Collins
 */
class Peptide extends BasePeptide
{

    const ARRAY_ID = 'id';

    const ARRAY_SEQUENCE = 'sequence';

    const ARRAY_SCORE = 'S';

    const ARRAY_IONS = 'IM';

    const ARRAY_MODIFICATIONS = 'mods';

    /**
     * Peptide ID
     *
     * @var int
     */
    private $id;

    /**
     * Number of ions matched from a search result
     *
     * @var int
     * @deprecated
     */
    private $ionsMatched;

    /**
     * Peptide score from a search result
     *
     * @var number
     * @deprecated
     */
    private $score;

    /**
     * Creates a new instance of this class with the specified peptide ID
     *
     * @param int $id
     *            Peptide ID value
     * @throws \InvalidArgumentException If peptide ID is not an integer
     */
    public function __construct($id)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException('Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        $this->id = $id;
    }

    /**
     * Sets the peptide score
     *
     * @param number $score
     *            The peptide score
     * @param int $ionsMatched
     *            The number of ions matched
     * @throws \InvalidArgumentException If the arguments do not match the data types
     * @deprecated Should use phpMs/Identification
     */
    public function setScore($score, $ionsMatched)
    {
        if (! is_float($score) && ! is_int($score)) {
            throw new \InvalidArgumentException('Argument 1 must be an int or float value. Valued passed is of type ' . gettype($score));
        }
        
        if (! is_int($ionsMatched)) {
            throw new \InvalidArgumentException('Argument 2 must be an int value. Valued passed is of type ' . gettype($ionsMatched));
        }
        
        $this->score = $score;
        $this->ionsMatched = $ionsMatched;
    }

    /**
     * Gets the ID value
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the peptides score value
     *
     * @return number
     * @deprecated Should use phpMs/Identification
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Gets the number of ions matched
     *
     * @return int
     * @deprecated Should use phpMs/Identification
     */
    public function getIonsMatched()
    {
        return $this->ionsMatched;
    }

    /**
     * Converts this peptide object into an array
     *
     * @return array
     */
    public function toArray()
    {
        $peptide = array();
        $peptide[Peptide::ARRAY_ID] = $this->getId();
        $peptide[Peptide::ARRAY_SEQUENCE] = $this->getSequence();
        if (! is_null($this->getScore()) && ! is_null($this->getIonsMatched())) {
            $peptide[Peptide::ARRAY_SCORE] = $this->getScore();
            $peptide[Peptide::ARRAY_IONS] = $this->getIonsMatched();
        }
        
        if ($this->isModified()) {
            $peptide[Peptide::ARRAY_MODIFICATIONS] = $this->toArrayMods();
        } else {
            $peptide[Peptide::ARRAY_MODIFICATIONS] = array();
        }
        
        return $peptide;
    }

    /**
     * Converts the mods object to array.
     * Performs merge on duplicate mods
     *
     * @return array
     */
    private function toArrayMods()
    {
        $unique = array();
        foreach ($this->getModifications() as $modification) {
            if (! isset($unique[$modification->getId()])) {
                $unique[$modification->getId()] = array(
                    'mod' => $modification,
                    Modification::ARRAY_OCCURRENCES => 0
                );
            }
            
            $unique[$modification->getId()][Modification::ARRAY_OCCURRENCES] ++;
        }
        
        $modArray = array();
        foreach ($unique as $mod) {
            $array = $mod['mod']->toArray();
            $array[Modification::ARRAY_OCCURRENCES] = $mod[Modification::ARRAY_OCCURRENCES];
            $modArray[] = $array;
        }
        return $modArray;
    }

    /**
     * Parses an array instance of this object, in the format that is produced by toArray.
     *
     * @param array $peptideArray
     *            The array object to parse
     * @throws \InvalidArgumentException If the array does not match the format
     * @return \pgb_liv\crowdsource\Core\Peptide
     */
    public static function fromArray(array $peptideArray)
    {
        if (! isset($peptideArray[Peptide::ARRAY_ID]) || ! is_int($peptideArray[Peptide::ARRAY_ID])) {
            throw new \InvalidArgumentException('A peptide "ID" must be an int value. Valued passed is of type ' . gettype($peptideArray[Peptide::ARRAY_ID]));
        }
        
        $peptide = new Peptide($peptideArray[Peptide::ARRAY_ID]);
        
        if (isset($peptideArray[Peptide::ARRAY_SEQUENCE])) {
            $peptide->setSequence($peptideArray[Peptide::ARRAY_SEQUENCE]);
        }
        
        if (isset($peptideArray[Peptide::ARRAY_SCORE]) && isset($peptideArray[Peptide::ARRAY_IONS])) {
            $peptide->setScore($peptideArray[Peptide::ARRAY_SCORE], $peptideArray[Peptide::ARRAY_IONS]);
        }
        
        if (isset($peptideArray[Peptide::ARRAY_MODIFICATIONS])) {
            foreach ($peptideArray[Peptide::ARRAY_MODIFICATIONS] as $modificationArray) {
                $modObject = Modification::fromArray($modificationArray);
                
                if (is_array($modObject)) {
                    $peptide->addModifications($modObject);
                } else {
                    $peptide->addModification($modObject);
                }
            }
        }
        
        return $peptide;
    }
}
