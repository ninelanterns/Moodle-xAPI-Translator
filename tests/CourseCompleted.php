<?php
namespace MXTranslator\Tests;
use \MXTranslator\Events\CourseCompleted as Event;

class CourseCompletedTest extends EventTest {
    protected static $recipe_name = 'course_completed';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }
    protected function constructInput() {
        return array_merge(parent::constructInput(), [
            'event' => $this->constructEvent('\core\event\course_completed'),
        ]);
    }
    private function constructEvent($event_name) {
        return [
            'eventname' => $event_name,
            'timecreated' => 1433946701,
        ];
    }
    protected function assertOutput($input, $output) {
        parent::assertOutput($input, $output);
        $this->assertCourse($input['course'], $output, 'course');
    }
}