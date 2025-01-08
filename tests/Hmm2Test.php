<?php


use olcaytaner\Hmm\Hmm2;
use PHPUnit\Framework\TestCase;

class Hmm2Test extends TestCase
{
    public function testViterbi()
    {
        $states = ["HOT", "COLD"];
        $observations = [["HOT", "HOT", "HOT"],
            ["HOT", "COLD", "COLD", "COLD"],
            ["HOT", "COLD", "HOT", "COLD"],
            ["COLD", "COLD", "COLD", "HOT", "HOT"],
            ["COLD", "HOT", "HOT", "COLD", "COLD"]];
        $emittedSymbols = [[3, 2, 3],
            [2, 2, 1, 1],
            [3, 1, 2, 1],
            [3, 1, 2, 2, 3],
            [1, 2, 3, 2, 1]];
        $hmm = new Hmm2($states, $observations, $emittedSymbols);
        $observed = [1, 1, 1, 1, 1, 1];
        $observedStates = $hmm->viterbi($observed);
        $this->assertEquals("COLD", $observedStates[0]);
        $this->assertEquals("COLD", $observedStates[1]);
        $this->assertEquals("COLD", $observedStates[2]);
        $this->assertEquals("COLD", $observedStates[3]);
        $this->assertEquals("COLD", $observedStates[4]);
        $this->assertEquals("COLD", $observedStates[5]);
        $observed = [1, 2, 3, 3, 2, 1];
        $observedStates = $hmm->viterbi($observed);
        $this->assertEquals("COLD", $observedStates[0]);
        $this->assertEquals("HOT", $observedStates[1]);
        $this->assertEquals("HOT", $observedStates[2]);
        $this->assertEquals("HOT", $observedStates[3]);
        $this->assertEquals("HOT", $observedStates[4]);
        $this->assertEquals("COLD", $observedStates[5]);
        $observed = [3, 3, 3, 3, 3, 3];
        $observedStates = $hmm->viterbi($observed);
        $this->assertEquals("HOT", $observedStates[0]);
        $this->assertEquals("HOT", $observedStates[1]);
        $this->assertEquals("HOT", $observedStates[2]);
        $this->assertEquals("HOT", $observedStates[3]);
        $this->assertEquals("HOT", $observedStates[4]);
        $this->assertEquals("HOT", $observedStates[5]);
    }
}
