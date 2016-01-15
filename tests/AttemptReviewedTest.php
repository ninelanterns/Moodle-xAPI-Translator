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
        $input = array_merge(parent::constructInput(), [
            'grade_items' => $this->constructGradeitems(),
            'questions' => $this->constructQuestions()
        ]);

        $input['attempt']->questions = $this->constructQuestionAttempts()

         return $input;
    }

    private function constructGradeitems() {
        return (object) [
            'grademin' => 0,
            'grademax' => 5,
            'gradepass' => 5
        ];
    }

    private function constructQuestionAttempts() {
        return (object) [
            'maxmark' => '5.0000000',
            'steps' => [
                (object)[
                    'sequencenumber' => 1,
                    'state' => 'todo',
                    'timecreated' => '1452867228',
                    'fraction' => null
                ,
                (object)[
                    'sequencenumber' => 2,
                    'state' => 'gradedright',
                    'timecreated' => '1452867232',
                    'fraction' => '1.0000000'
                ,
            ]
        ];
    }

    private function constructQuestions() {
        return (object) [
            'name' => 'test name',
            'questiontext' => 'test questiontext',
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
        $this->assertEquals((float) $input['grade_items']->grademin, $output['attempt_score_min']);
        $this->assertEquals((float) $input['grade_items']->grademax, $output['attempt_score_max']);
        $this->assertEquals(($input['attempt']->sumgrades >= $input['grade_items']->gradepass), $output['attempt_success']);
        if ($output['attempt_score_scaled']  >= 0) {
            $this->assertEquals($output['attempt_score_scaled'], $output['attempt_score_raw'] / $output['attempt_score_max']);
        }
        else
        {
            $this->assertEquals($output['attempt_score_scaled'], $output['attempt_score_raw'] / $output['attempt_score_min']);
        }
    }
}
