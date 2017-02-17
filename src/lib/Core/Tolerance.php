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
 * Class to encapsulate tolerance values with their unit type.
 *
 * @author Andrew Collins
 */
class Tolerance
{

    const DA = 'da';

    const PPM = 'ppm';

    private $tolerance;

    private $unit;

    /**
     * Creates a new instance of this object with a specified tolerance value and unit type
     *
     * @param float $tolerance
     *            Tolerance numeric value
     * @param string $unit
     *            Tolerance unit type (See Tolerance::DA and Tolerance::PPM)
     * @throws \InvalidArgumentException If Argument 1 is not a float or argument 2 is not an acceptable unit type
     */
    public function __construct($tolerance, $unit)
    {
        if (! is_float($tolerance) && ! is_int($tolerance)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be a float or int value. Valued passed is of type ' . gettype($tolerance));
        }
        
        if ($unit != Tolerance::DA && $unit != Tolerance::PPM) {
            throw new \InvalidArgumentException('Argument 2 must equal "da" or "ppm". Valued passed is "' . $unit . '"');
        }
        
        $this->tolerance = $tolerance;
        $this->unit = $unit;
    }

    /**
     * Gets the numeric tolerance value.
     *
     * @return float
     */
    public function getTolerance()
    {
        return $this->tolerance;
    }

    /**
     * Gets the tolerance unit type, Tolerance::PPM or Tolerance::DA
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }
}