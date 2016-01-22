<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\AttemptAbandoned as Event;

class AttemptAbandonedTest extends AttemptReviewedTest {
    protected static $recipe_name = 'attempt_completed';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }

    protected function assertAttempt($input, $output) {
        parent::assertAttempt($input, $output);
    }
}