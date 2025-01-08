<?php

namespace olcaytaner\Hmm;

use olcaytaner\DataStructure\CounterHashMap;
use olcaytaner\Math\Matrix;

abstract class Hmm
{
    protected Matrix $transitionProbabilities;
    protected array $stateIndexes;
    protected array $states;
    protected int $stateCount;

    abstract protected function calculatePi(array $observations): void;

    abstract protected function calculateTransitionProbabilities(array $observations): void;

    abstract protected function viterbi(array $s): array;

    /**
     * A constructor of {@link Hmm} class which takes a {@link Set} of states, an array of observations (which also
     * consists of an array of states) and an array of instances (which also consists of an array of emitted symbols).
     * The constructor initializes the state array with the set of states and uses observations and emitted symbols
     * to calculate the emission probabilities for those states.
     *
     * @param array $states A {@link Set} of states, consisting of all possible states for this problem.
     * @param array $observations An array of instances, where each instance consists of an array of states.
     * @param array $emittedSymbols An array of instances, where each instance consists of an array of symbols.
     */
    public function __construct(array $states, array $observations, array $emittedSymbols)
    {
        $i = 0;
        $this->stateCount = count($states);
        $this->states = [];
        $this->stateIndexes = [];
        foreach ($states as $state) {
            $this->stateIndexes[$state] = $i;
            $i++;
        }
        $this->calculatePi($observations);
        foreach ($states as $state) {
            $emissionProbabilities = $this->calculateEmissionProbabilities($state, $observations, $emittedSymbols);
            $this->states[] = new HmmState($state, $emissionProbabilities);
        }
        $this->calculateTransitionProbabilities($observations);
    }

    /**
     * calculateEmissionProbabilities calculates the emission probabilities for a specific state. The method takes the state,
     * an array of observations (which also consists of an array of states) and an array of instances (which also consists
     * of an array of emitted symbols).
     *
     * @param mixed $state The state for which emission probabilities will be calculated.
     * @param array $observations An array of instances, where each instance consists of an array of states.
     * @param array $emittedSymbols An array of instances, where each instance consists of an array of symbols.
     * @return array A {@link Map} Emission probabilities for a single state. Contains a probability for each symbol emitted.
     */
    public function calculateEmissionProbabilities(mixed $state, array $observations, array $emittedSymbols): array
    {
        $counts = new CounterHashMap();
        $emissionProbabilities = [];
        for ($i = 0; $i < count($observations); $i++) {
            for ($j = 0; $j < count($observations[$i]); $j++) {
                $currentState = $observations[$i][$j];
                $currentSymbol = $emittedSymbols[$i][$j];
                if ($currentState === $state) {
                    $counts->put($currentSymbol);
                }
            }
        }
        $sum = $counts->sumOfCounts();
        foreach ($counts->keys() as $key) {
            $emissionProbabilities[$key] = $counts->count($key) / $sum;
        }
        return $emissionProbabilities;
    }

    /**
     * safeLog calculates the logarithm of a number. If the number is less than 0, the logarithm is not defined, therefore
     * the function returns -Infinity.
     *
     * @param float $x Input number
     * @return float the logarithm of x. If x less than 0 return -infinity.
     */
    public function safeLog(float $x): float{
        if ($x < 0) {
            return -INF;
        } else {
            return log($x);
        }
    }
}