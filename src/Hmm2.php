<?php

namespace olcaytaner\Hmm;

use olcaytaner\Math\Matrix;
use olcaytaner\Math\Vector;

class Hmm2 extends Hmm
{
    private Matrix $pi;

    /**
     * A constructor of {@link Hmm2} class which takes a {@link Set} of states, an array of observations (which also
     * consists of an array of states) and an array of instances (which also consists of an array of emitted symbols).
     * The constructor calls its super method to calculate the emission probabilities for those states.
     *
     * @param array $states A {@link Set} of states, consisting of all possible states for this problem.
     * @param array $observations An array of instances, where each instance consists of an array of states.
     * @param array $emittedSymbols An array of instances, where each instance consists of an array of symbols.
     */
    public function __construct(array $states, array $observations, array $emittedSymbols){
        parent::__construct($states, $observations, $emittedSymbols);
    }

    /**
     * calculatePi calculates the prior probability matrix (initial probabilities for each state combinations)
     * from a set of observations. For each observation, the function extracts the first and second states in
     * that observation.  Normalizing the counts of the pair of states returns us the prior probabilities for each
     * pair of states.
     *
     * @param array $observations A set of observations used to calculate the prior probabilities.
     */
    protected function calculatePi(array $observations): void
    {
        $this->pi = new Matrix($this->stateCount, $this->stateCount);
        foreach ($observations as $observation) {
            $first = $this->stateIndexes[$observation[0]];
            $second = $this->stateIndexes[$observation[1]];
            $this->pi->increment($first, $second);
        }
        $this->pi->columnWiseNormalize();
    }

    /**
     * calculateTransitionProbabilities calculates the transition probabilities matrix from each state to another state.
     * For each observation and for each transition in each observation, the function gets the states. Normalizing the
     * counts of the three of states returns us the transition probabilities.
     *
     * @param array $observations A set of observations used to calculate the transition probabilities.
     */
    protected function calculateTransitionProbabilities(array $observations): void
    {
        $this->transitionProbabilities = new Matrix($this->stateCount * $this->stateCount, $this->stateCount);
        foreach ($observations as $current) {
            for ($j = 0; $j < count($current) - 2; $j++) {
                $from1 = $this->stateIndexes[$current[$j]];
                $from2 = $this->stateIndexes[$current[$j + 1]];
                $to = $this->stateIndexes[$current[$j + 2]];
                $this->transitionProbabilities->increment($from1 * $this->stateCount + $from2, $to);
            }
        }
        $this->transitionProbabilities->columnWiseNormalize();
    }

    /**
     * logOfColumn calculates the logarithm of each value in a specific column in the transition probability matrix.
     *
     * @param int $column Column index of the transition probability matrix.
     * @return Vector A vector consisting of the logarithm of each value in the column in the transition probability matrix.
     */
    private function logOfColumn(int $column): Vector
    {
        $result = new Vector(0, 0);
        for ($i = 0; $i < $this->stateCount; $i++) {
            $result->add($this->safeLog($this->transitionProbabilities->getValue($i * $this->stateCount + (int) ($column / $this->stateCount), $column % $this->stateCount)));
        }
        return $result;
    }

    /**
     * viterbi calculates the most probable state sequence for a set of observed symbols.
     *
     * @param array $s A set of observed symbols.
     * @return array The most probable state sequence as an {@link Array}.
     */
    public function viterbi(array $s): array
    {
        $sequenceLength = count($s);
        $gamma = new Matrix($sequenceLength, $this->stateCount * $this->stateCount);
        $phi = new Matrix($sequenceLength, $this->stateCount * $this->stateCount);
        $qs = new Vector($sequenceLength, 0);
        $result = [];
        /*Initialize*/
        $emission1 = $s[0];
        $emission2 = $s[1];
        for ($i = 0; $i < $this->stateCount; $i++) {
            for ($j = 0; $j < $this->stateCount; $j++) {
                if ($this->states[$i] instanceof HmmState){
                    $observationLikelihood = $this->states[$i]->getEmitProb($emission1) * $this->states[$j]->getEmitProb($emission2);
                    $gamma->setValue(1, $i * $this->stateCount + $j, $this->safeLog($this->pi->getValue($i, $j)) + $this->safeLog($observationLikelihood));
                }
            }
        }
        /*Iterate Dynamic Programming*/
        for ($t = 2; $t < $sequenceLength; $t++) {
            $emission = $s[$t];
            for ($j = 0; $j < $this->stateCount * $this->stateCount; $j++) {
                $current = $this->logOfColumn($j);
                $previous = $gamma->getRowVector($t - 1)->skipVector($this->stateCount, (int)($j / $this->stateCount));
                $current->addVector($previous);
                $maxIndex = $current->maxIndex();
                $observationLikelihood = $this->states[$j % $this->stateCount]->getEmitProb($emission);
                $gamma->setValue($t, $j, $current->getValue($maxIndex) + $this->safeLog($observationLikelihood));
                $phi->setValue($t, $j, (int) ($maxIndex * $this->stateCount + $j / $this->stateCount));
            }
        }
        /*Backtrack pointers*/
        $qs->setValue($sequenceLength - 1, $gamma->getRowVector($sequenceLength - 1)->maxIndex());
        array_unshift($result, $this->states[$qs->getValue($sequenceLength - 1) % $this->stateCount]->getState());
        for ($i = $sequenceLength - 2; $i >= 1; $i--) {
            $qs->setValue($i, $phi->getValue($i + 1, $qs->getValue($i + 1)));
            array_unshift($result, $this->states[$qs->getValue($i) % $this->stateCount]->getState());
        }
        array_unshift($result, $this->states[(int) ($qs->getValue(1) / $this->stateCount)]->getState());
        return $result;
    }
}