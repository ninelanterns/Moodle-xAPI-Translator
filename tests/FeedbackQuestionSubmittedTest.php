<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\FeedbackQuestionSubmitted as Event;

class FeedbackQuestionSubmittedTest extends FeedbackSubmittedTest {
    protected static $recipe_name = 'attempt_question_completed';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }

    protected function assertOutput($input, $output) {
        parent::assertOutput($input, $output);
        $this->assertEquals($input['questions']{0}->name, $output['question_name']);
        $this->assertEquals($input['questions']{0}->name, $output['question_description']);
        $this->assertEquals($input['questions']{0}->url, $output['question_url']);
        $this->assertEquals($input['attempt']->responses{0}->value, $output['attempt_response']);
        $this->assertEquals(null, $output['interaction_correct_responses']);
        $this->assertEquals('likert', $output['interaction_type']);
        $this->assertEquals((object) [
            "0" => "Not selected",
            '1' => "incorrect",
            '2' => "correct"
        ], $output['interaction_scale']);
    }

    protected function assertAttempt($input, $output) {
        // Overides parent and does nothing
    }

}