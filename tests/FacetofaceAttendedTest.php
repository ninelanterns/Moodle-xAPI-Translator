<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\FacetofaceAttend as Event;

class FacetofaceAttendTest extends FacetofaceEnrolTest {
    protected static $recipe_name = 'training_session_attend';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }

    protected function constructInput() {
        return array_merge(parent::constructInput(), [
            'signups' => [
                "1" => $this->constructSignup("1"),
                "2" => $this->constructSignup("2")
            ]
        ]);
    }

    private function constructSignup($id) {
        $signups =  (object) [  
            "id" => $id,
            "sessionid" => "1",
            "userid" => "1",
            "mailedreminder" => "0",
            "discountcode" => null,
            "notificationtype" => "3",
            "statuses" => [
                "1" => $this->constructStatus("1"),
                "2" => $this->constructStatus("2"),
                "3" => $this->constructFinalStatus("3"),
            ],
            "attendee" => $this->constructUser()
        ];

        return $signups;
    }

    private function constructStatus($id) {
        return (object) [
            "id" => $id,
            "signupid" => "4",
            "statuscode" => "90",
            "superceded" => "1",
            "grade" => "50.00000",
            "note" => "",
            "advice" => null,
            "createdby" => "1",
            "timecreated" => "146711713".$id
        ];
    }

    private function constructFinalStatus($id) {
        return (object) [
            "id" => $id,
            "signupid" => "4",
            "statuscode" => "100",
            "superceded" => "1",
            "grade" => "100.00000",
            "note" => "",
            "advice" => null,
            "createdby" => "1",
            "timecreated" => "146711713".$id
        ];
    }

    protected function assertOutput($input, $output) {
        parent::assertOutput($input, $output);
        $this->assertEquals($input['signups']['1']->attendee->id, $output['attendee_id']);
        $this->assertEquals($input['signups']['1']->attendee->url, $output['attendee_url']);
        $this->assertEquals($input['signups']['1']->attendee->fullname, $output['attendee_name']);
        $this->assertEquals("PT".$input['session']->duration."S", $output['attempt_duration']);
        $this->assertEquals(true, $output['attempt_completion']);
    }
}
