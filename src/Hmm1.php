<?php

namespace olcaytaner\Hmm;

use olcaytaner\Math\Matrix;
use olcaytaner\Math\Vector;

class Hmm1 extends Hmm
{
    private Vector $pi;

    /**
     * A constructor of {@link Hmm1} class which takes a {@link Set} of states, an array of observations (which also
     * consists of an array of states) and an array of instances (which also consists of an array of emitted symbols).
     * The constructor calls its super method to calculate the emission probabilities for those states.
     *
     * @param array $states A {@link Set} of states, consisting of all possible states for this problem.
     * @param array $observations An array of instances, where each instance consists of an array of states.
     * @param array $emittedSymbols An array of instances, where each instance consists of an array of symbols.
     */
    public function __construct(array $states, array $observations, array $emittedSymbols)
    {
        parent::__construct($states, $observations, $emittedSymbols);
    }

    /**
     * calculatePi calculates the prior probability vector (initial probabilities for each state) from a set of
     * observations. For each observation, the function extracts the first state in that observation. Normalizing the
     * counts of the states returns us the prior probabilities for each state.
     *
     * @param array $observations A set of observations used to calculate the prior probabilities.
     */
    protected function calculatePi(array $observations): void
    {
        $this->pi = new Vector($this->stateCount, 0.0);
        foreach ($observations as $observation) {
            $index = $this->stateIndexes[$observation[0]];
            $this->pi->addValue($index, 1.0);
        }
        $this->pi->l1Normalize();
    }

    /**
     * calculateTransitionProbabilities calculates the transition probabilities matrix from each state to another state.
     * For each observation and for each transition in each observation, the function gets the states. Normalizing the
     * counts of the pair of states returns us the transition probabilities.
     *
     * @param array $observations A set of observations used to calculate the transition probabilities.
     */
    protected function calculateTransitionProbabilities(array $observations): void
    {
        $this->transitionProbabilities = new Matrix($this->stateCount, $this->stateCount);
        foreach ($observations as $current) {
            for ($j = 0; $j < count($current) - 1; $j++) {
                $from = $this->stateIndexes[$current[$j]];
                $to = $this->stateIndexes[$current[$j + 1]];
                $this->transitionProbabilities->increment($from, $to);
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
            $result->add($this->safeLog($this->transitionProbabilities->getValue($i, $column)));
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
        $gamma = new Matrix($sequenceLength, $this->stateCount);
        $phi = new Matrix($sequenceLength, $this->stateCount);
        $qs = new Vector($sequenceLength, 0);
        $result = [];
        /*Initialize*/
        $emission = $s[0];
        for ($i = 0; $i < $this->stateCount; $i++) {
            if ($this->states[$i] instanceof HmmState){
                $observationLikelihood = $this->states[$i]->getEmitProb($emission);
                $gamma->setValue(0, $i, $this->safeLog($this->pi->getValue($i)) + $this->safeLog($observationLikelihood));
            }
        }
        /*Iterate Dynamic Programming*/
        for ($t = 1; $t < $sequenceLength; $t++) {
            $emission = $s[$t];
            for ($j = 0; $j < $this->stateCount; $j++) {
                $tempArray = $this->logOfColumn($j);
                $tempArray->addVector($gamma->getRowVector($t - 1));
                $maxIndex = $tempArray->maxIndex();
                $observationLikelihood = $this->states[$j]->getEmitProb($emission);
                $gamma->setValue($t, $j, $tempArray->getValue($maxIndex) + $this->safeLog($observationLikelihood));
                $phi->setValue($t, $j, $maxIndex);
            }
        }
        /*Backtrack pointers*/
        $qs->setValue($sequenceLength - 1, $gamma->getRowVector($sequenceLength - 1)->maxIndex());
        array_unshift($result, $this->states[$qs->getValue($sequenceLength - 1)]->getState());
        for ($i = $sequenceLength - 2; $i >= 0; $i--) {
            $qs->setValue($i, $phi->getValue($i + 1, $qs->getValue($i + 1)));
            array_unshift($result, $this->states[$qs->getValue($i)]->getState());
        }
        return $result;
    }
}