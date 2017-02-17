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

/**
 * Class to encapsulate peptide properties
 *
 * @author Andrew Collins
 */
class Peptide
{

    /**
     * Peptide ID
     *
     * @var int
     */
    private $id;

    /**
     * Peptide sequence
     *
     * @var string
     */
    private $sequence;

    /**
     * Number of ions matched from a search result
     *
     * @var int
     */
    private $ionsMatched;

    /**
     * Peptide score from a search result
     *
     * @var number
     */
    private $score;

    /**
     * Array of modifications affecting this peptide
     *
     * @var array
     */
    private $modifications = array();

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
            throw new \InvalidArgumentException(
                'Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        $this->id = $id;
    }

    /**
     * Sets the peptide sequence
     *
     * @param string $sequence
     *            The sequence
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * Sets the peptide score
     *
     * @param number $score
     *            The peptide score
     * @param int $ionsMatched
     *            The number of ions matched
     * @throws \InvalidArgumentException If the arguments do not match the data types
     */
    public function setScore($score, $ionsMatched)
    {
        if (! is_float($score) && ! is_int($score)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an int or float value. Valued passed is of type ' . gettype($score));
        }
        
        if (! is_int($ionsMatched)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be an int value. Valued passed is of type ' . gettype($ionsMatched));
        }
        
        $this->score = $score;
        $this->ionsMatched = $ionsMatched;
    }

    /**
     * Adds the specified modification to this peptide
     *
     * @param Modification $modification
     *            Modification object to apply
     */
    public function addModification(Modification $modification)
    {
        $this->modifications[$modification->getId()] = $modification;
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
     * Gets the sequence
     *
     * @return string
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Gets the peptides score value
     *
     * @return number
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Gets the number of ions matched
     *
     * @return int
     */
    public function getIonsMatched()
    {
        return $this->ionsMatched;
    }

    /**
     * Gets the modifications
     */
    public function getModifications()
    {
        return $this->modifications;
    }

    /**
     * Returns whether this peptide contains modifications or not
     *
     * @return boolean True if the object contains modifications
     */
    public function isModified()
    {
        return count($this->modifications) != 0;
    }
}
