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

    const ARRAY_ID = 'id';

    const ARRAY_MASS = 'mass';

    const ARRAY_RESIDUES = 'residues';

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
    public function __construct($id, $monoMass = null, array $residues = null)
    {
        if (! is_int($id)) {
            throw new \InvalidArgumentException('Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
        }
        
        $this->id = $id;
        
        if (! is_null($monoMass)) {
            $this->setMonoisotopicMass($monoMass);
        }
        
        if (! is_null($residues)) {
            $this->setResidues($residues);
        }
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
     * Sets the monoisotopic mass for this modification
     *
     * @param float $monoMass
     *            The monoisotopic mass to set
     * @throws \InvalidArgumentException If argument 1 is not of type float
     */
    public function setMonoisotopicMass($monoMass)
    {
        if (! is_float($monoMass)) {
            throw new \InvalidArgumentException('Argument 1 must be a float value. Valued passed is of type ' . gettype($monoMass));
        }
        
        $this->monoMass = $monoMass;
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
     * Set residues for this modification
     *
     * @param array $residues
     *            Array of residues this modification may occur on
     * @throws \InvalidArgumentException If argument 1 is not of type float
     */
    public function setResidues(array $residues)
    {
        if (empty($residues)) {
            throw new \InvalidArgumentException('Argument 1 must not be empty.');
        } else {
            foreach ($residues as $residue) {
                if (strlen($residue) != 1) {
                    throw new \InvalidArgumentException('Argument 1 must be an array of single char values. Value passed is of length ' . strlen($residue));
                }
            }
        }
        
        // Force sort order
        sort($residues);
        
        // Force unique residue positions
        $this->residues = array_combine($residues, $residues);
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

    /**
     * Creates a new instance of this class from an array of properties
     *
     * @param array $modArray
     *            Array of properties. See ARRAY_... for property names
     * @return \pgb_liv\crowdsource\Core\Modification
     */
    public static function fromArray(array $modArray)
    {
        $modification = new Modification($modArray[Modification::ARRAY_ID]);
        
        if (isset($modArray[Modification::ARRAY_MASS])) {
            $modification->setMonoisotopicMass($modArray[Modification::ARRAY_MASS]);
        }
        
        if (isset($modArray[Modification::ARRAY_RESIDUES])) {
            $residues = str_split($modArray[Modification::ARRAY_RESIDUES]);
            $modification->setResidues($residues);
        }
        
        return $modification;
    }

    /**
     * Converts this modification to an array
     *
     * @return Array of modification properties
     */
    public function toArray()
    {
        $modification[Modification::ARRAY_ID] = $this->getId();
        $modification[Modification::ARRAY_MASS] = $this->getMonoisotopicMass();
        $modification[Modification::ARRAY_RESIDUES] = implode('', $this->getResidues());
        
        return $modification;
    }
}
