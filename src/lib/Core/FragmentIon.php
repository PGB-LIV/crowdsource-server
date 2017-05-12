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

use pgb_liv\php_ms\Core\Spectra\FragmentIon as BaseFragmentIon;

/**
 * Class that encapsulates a mass/charge and intensity as a single object
 *
 * @author Andrew Collins
 */
class FragmentIon extends BaseFragmentIon
{

    const ARRAY_MZ = 'mz';

    const ARRAY_INTENSITY = 'intensity';

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
        $this->setMassCharge($mz);
        $this->setIntensity($intensity);
    }

    /**
     * Gets the Mass/Charge value of this fragment ion
     *
     * @return float
     * @deprecated Use getMassCharge()
     */
    public function getMz()
    {
        return $this->getMassCharge();
    }

    /**
     * Converts this instance to an array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            FragmentIon::ARRAY_MZ => $this->getMassCharge(),
            FragmentIon::ARRAY_INTENSITY => $this->getIntensity()
        );
    }

    public static function fromArray($fragmentArray)
    {
        return new FragmentIon($fragmentArray[FragmentIon::ARRAY_MZ], $fragmentArray[FragmentIon::ARRAY_INTENSITY]);
    }
}
