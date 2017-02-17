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
 * Class that encapsulates a mass/charge and intensity as a single object
 *
 * @author Andrew Collins
 */
class FragmentIon
{

    /**
     * The Mass/Charge value
     *
     * @var float
     */
    private $mz;

    /**
     * The intensity value
     *
     * @var float
     */
    private $intensity;

    /**
     * Creates a new fragmention ion object with the specified mass/charge and intensity value
     *
     * @param float $mz
     *            Mass/Charge value
     * @param float $intensity
     *            Intensity value
     * @throws \InvalidArgumentException Thrown if either argument is not of type float
     */
    public function __construct($mz, $intensity)
    {
        if (! is_float($mz)) {
            throw new \InvalidArgumentException(
                'Argument 1 must be a float value. Valued passed is of type ' . gettype($mz));
        }
        
        if (! is_float($intensity)) {
            throw new \InvalidArgumentException(
                'Argument 2 must be a float value. Valued passed is of type ' . gettype($intensity));
        }
        
        $this->mz = $mz;
        $this->intensity = $intensity;
    }

    /**
     * Gets the Mass/Charge value of this fragment ion
     *
     * @return float
     */
    public function getMz()
    {
        return $this->mz;
    }

    /**
     * Gets the intensity value of this fragment ion
     *
     * @return float
     */
    public function getIntensity()
    {
        return $this->intensity;
    }
}