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

    protected function assertOutput($input, $output) {
        parent::assertOutput($input, $output);
        $this->assertAttempt($input['attempt'], $output);
        $this->assertGradeItems($input, $output);
    }

    protected function assertAttempt($input, $output) {
        parent::assertAttempt($input, $output);
        $this->assertEquals((float) $input->sumgrades, $output['attempt_score_raw']);
        $this->assertEquals($input->state === 'finished', $output['attempt_completed']);
    }

    protected function assertGradeItems($input, $output) {
        $this->assertEquals((float) $input['gradeitems']->grademin, $output['attempt_score_min']);
        $this->assertEquals((float) $input['gradeitems']->grademax, $output['attempt_score_max']);
        $this->assertEquals(($input['attempt']->sumgrades >= $input['gradeitems']->gradepass), $output['attempt_success']);
    }
}
