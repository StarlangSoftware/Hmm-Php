<?php

namespace olcaytaner\Hmm;

class HmmState
{
    private array $emissionProbabilities;
    private mixed $state;

    /**
     * A constructor of {@link HmmState} class which takes a state and emission probabilities as inputs and
     * initializes corresponding class variable with these inputs.
     *
     * @param mixed $state Data for this state.
     * @param array $emissionProbabilities Emission probabilities for this state
     */
    public function __construct(mixed $state, array $emissionProbabilities)
    {
        $this->emissionProbabilities = $emissionProbabilities;
        $this->state = $state;
    }

    /**
     * Accessor method for the state variable.
     *
     * @return mixed $state variable.
     */
    public function getState(): mixed
    {
        return $this->state;
    }

    /**
     * getEmitProb method returns the emission probability for a specific symbol.
     *
     * @param mixed $symbol Symbol for which the emission probability will be get.
     * @return float Emission probability for a specific symbol.
     */
    public function getEmitProb(mixed $symbol): float
    {
        if (isset($this->emissionProbabilities[$symbol])) {
            return $this->emissionProbabilities[$symbol];
        } else {
            return 0.0;
        }
    }
}