<?php
namespace pgb_liv\crowdsource;

use pgb_liv\php_ms\Core\Identification;

class FalseDiscoveryRate
{

    private $falseDiscoveryRates = array();

    public function __construct()
    {}

    /**
     *
     * @todo rename
     * @param Identification[] $identifications
     * @param string $scoreKey
     */
    public function getFdr(array $identifications, $scoreKey, $sort = SORT_DESC)
    {
        $scores[] = array();
        $targetDecoy[] = array();
        foreach ($identifications as $identification) {
            $scores[] = (float) $identification->getScore($scoreKey);
            $isDecoy = false;
            
            if ($identification->getSequence()->isDecoy()) {
                $isDecoy = true;
            }
            
            // Peptide may not contain the field, but the protein might.
            foreach ($identification->getSequence()->getProteins() as $proteinEntry) {
                $protein = $proteinEntry->getProtein();
                if ($protein->isDecoy()) {
                    $isDecoy = true;
                }
            }
            
            $targetDecoy[] = $isDecoy;
        }
        
        $this->calculateFdr($scores, $targetDecoy, $sort);
    }

    /**
     * Gets the score that corresponds to the target FDR
     *
     * @param float $targetRate
     */
    public function getScore($targetRate)
    {
        $score = 0;
        foreach ($this->falseDiscoveryRates as $falseDiscoryRate) {
            
            if ($falseDiscoryRate['FDR'] < $targetRate) {
                $score = $falseDiscoryRate['score'];
                
                continue;
            }
            
            break;
        }
        
        return $score;
    }

    /**
     * Gets the number of matches for the target FDR
     *
     * @param float $targetRate
     */
    public function getMatches($targetRate)
    {
        $matches = 0;
        foreach ($this->falseDiscoveryRates as $falseDiscoryRate) {
            if ($falseDiscoryRate['FDR'] <= $targetRate) {
                $matches ++;
                continue;
            }
            
            break;
        }
        
        return $matches;
    }

    public function getFalseDiscoryRates()
    {
        return $this->falseDiscoveryRates;
    }

    /**
     *
     * @param float[] $scores
     * @param bool[] $targetDecoy
     */
    private function calculateFdr(array $scores, array $targetDecoy, $sort)
    {
        if ($sort == SORT_DESC) {
            arsort($scores, SORT_NUMERIC);
        } else {
            asort($scores, SORT_NUMERIC);
        }
        
        $V = 0;
        $S = 0;
        foreach ($scores as $scoreKey => $score) {
            if ($targetDecoy[$scoreKey]) {
                $V ++;
            } else {
                $S ++;
            }
            
            $fdr = 0;
            if ($V + $S > 1) {
                $fdr = $V / ($V + $S);
            }
            
            $this->falseDiscoveryRates[] = array(
                'FDR' => $fdr,
                'score' => $score
            );
        }
    }
}

