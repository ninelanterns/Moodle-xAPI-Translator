<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\AttemptReviewed as Event;

class AttemptReviewedTest extends AttemptStartedTest {
    protected static $recipe_name = 'attempt_completed';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }

    protected function constructInput() {
        return array_merge(parent::constructInput(), [
            'gradeitems' => $this->constructGradeitems()
        ]);
    }

    private function constructGradeitems() {
        return (object) [
            'grademin' => 0,
            'grademax' => 5,
            'gradepass' => 5
        ];
    }

    protected function assertAttempt($input, $output) {
        parent::assertAttempt($input, $output);
        $this->assertEquals((float) $input->sumgrades, $output['attempt_score_raw']);
        $this->assertEquals((float) $input->grademin, $output['attempt_score_min']);
        $this->assertEquals((float) $input->grademax, $output['attempt_score_max']);
        $this->assertEquals(($input->sumgrades >= $input->gradepass), $output['attempt_success']);
        $this->assertEquals($input->state === 'finished', $output['attempt_completed']);
    }
}
