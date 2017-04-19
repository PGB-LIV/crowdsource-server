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

use pgb_liv\php_ms\Core\Modification as BaseModification;

/**
 * Modification class to encapsulate modification ID, Monoisotopic mass and and residues found on.
 *
 * @author Andrew Collins
 */
class Modification extends BaseModification
{

    const ARRAY_ID = 'id';

    const ARRAY_MASS = 'mass';

    const ARRAY_RESIDUES = 'residues';

    const ARRAY_LOCATION = 'position';

    const ARRAY_OCCURRENCES = 'num';

    /**
     * Modification ID
     *
     * @var int
     */
    private $id;

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
            throw new \InvalidArgumentException(
                'Argument 1 must be an int value. Valued passed is of type ' . gettype($id));
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
     * Creates a new instance of this class from an array of properties.
     * Method will return an array if ARRAY_OCCURRENCES > 1 or ARRAY_LOCATION is an array
     *
     * @param array $modificationArray
     *            Array of properties. See ARRAY_... for property names
     * @return \pgb_liv\crowdsource\Core\Modification | array
     */
    public static function fromArray(array $modificationArray)
    {
        $occurrences = 1;
        if (isset($modificationArray[Modification::ARRAY_OCCURRENCES])) {
            $occurrences = $modificationArray[Modification::ARRAY_OCCURRENCES];
        } elseif (isset($modificationArray[Modification::ARRAY_LOCATION]) &&
             is_array($modificationArray[Modification::ARRAY_LOCATION])) {
            $occurrences = count($modificationArray[Modification::ARRAY_LOCATION]);
        }
        
        $modifications = array();
        for ($i = 0; $i < $occurrences; $i ++) {
            $modification = new Modification($modificationArray[Modification::ARRAY_ID]);
            
            if (isset($modificationArray[Modification::ARRAY_MASS])) {
                $modification->setMonoisotopicMass($modificationArray[Modification::ARRAY_MASS]);
            }
            
            if (isset($modificationArray[Modification::ARRAY_RESIDUES])) {
                $residues = str_split($modificationArray[Modification::ARRAY_RESIDUES]);
                $modification->setResidues($residues);
            }
            
            if (isset($modificationArray[Modification::ARRAY_LOCATION])) {
                if (is_array($modificationArray[Modification::ARRAY_LOCATION])) {
                    $modification->setLocation($modificationArray[Modification::ARRAY_LOCATION][$i]);
                } else {
                    $modification->setLocation($modificationArray[Modification::ARRAY_LOCATION]);
                }
            }
            
            if ($occurrences == 1) {
                return $modification;
            }
            
            $modifications[] = $modification;
        }
        
        return $modifications;
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
        
        if (! is_null($this->getLocation())) {
            $modification[Modification::ARRAY_LOCATION] = $this->getLocation();
        }
        
        return $modification;
    }
}
