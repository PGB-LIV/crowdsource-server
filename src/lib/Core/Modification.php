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
 * Modification class to encapsulate modification ID, Monoisotopic mass and and residues found on.
 *
 * @author Andrew Collins
 */
class Modification
{

    /**
     * Modification ID
     *
     * @var int
     */
    private $id;

    /**
     * Monoisotopic Mass
     *
     * @var float
     */
    private $monoMass;

    /**
     * Residues modification found on
     *
     * @var array
     */
    private $residues = array();

    /**
     * Creates a new instance of this clas with the specified values
     *
     * @param int $id
     *            The modification ID
     * @param float $monoMass
     *            The monoisotopic mass value
     * @param array $residues
     *            An array of residues this modification occurs on
     * @throws \InvalidArgumentException If any argument does not match the specified data types
     */
    public function __construct($id, $monoMass, array $residues)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        if (! is_float($monoMass)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be a float value. Valued passed is of type ' . gettype($monoMass));
        }
        
        if (empty($residues)) {
            throw new \InvalidArgumentException('Argument 3 must not be empty.');
        } else {
            foreach ($residues as $residue) {
                if (strlen($residue) != 1) {
                    throw new \InvalidArgumentException(
                        'Argument 3 must be an array of single char values. Value passed is of length ' .
                             strlen($residue));
                }
            }
        }
        $this->id = $id;
        $this->monoMass = $monoMass;
        
        // Force sort order
        sort($residues);
        // Force unique residue positions
        $this->residues = array_combine($residues, $residues);
    }

    /**
     * Gets the modification ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the monoisotopic mass
     *
     * @return float
     */
    public function getMonoisotopicMass()
    {
        return $this->monoMass;
    }

    /**
     * Gets the residues the modification is assosciated with
     *
     * @return array
     */
    public function getResidues()
    {
        return array_values($this->residues);
    }
}