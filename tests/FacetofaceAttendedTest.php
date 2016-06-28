<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\FacetofaceAttend as Event;

class FacetofaceAttendTest extends ModuleViewedTest {
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
            'session' => $this->constructSession(),
            'signups' => [
                "1" => $this->constructSignup("1"),
                "2" => $this->constructSignup("2")
            ]
        ]);
    }

    private function constructSession() {
        return (object) [
            "id" => "1",
            "facetoface" => "1",
            "capacity" => "10",
            "allowoverbook" => "0",
            "details" => "",
            "datetimeknown" => "0",
            "duration" => "123456",
            "normalcost" => "0",
            "discountcost" => "0",
            "timecreated" => "1464179438",
            "timemodified" => "0",
            "type" => "facetoface_sessions",
            "dates": [
                "1": (object) [
                    "id" => "1",
                    "sessionid" => "1",
                    "timestart" => "1464179400",
                    "timefinish" => "1464179400"
                ]
            ]
            'url' => 'http://www.example.com/signup_url',
        ];
    }

    private function constructSignup($id) {
        $signups =  (object) [  
            "id": $id,
            "sessionid": "1",
            "userid": "1",
            "mailedreminder": "0",
            "discountcode": null,
            "notificationtype": "3",
            "statuses" => [
                "1" => constructStatus("1"),
                "2" => constructStatus("2"),
                "3" => constructFinalStatus("3"),
            ],
            "attendee" => $this->constructUser()
        ];

        return $signups;
    }

    private function constructStatus($id) {
        return (object) [
            "id": $id,
            "signupid": "4",
            "statuscode": "90",
            "superceded": "1",
            "grade": "50.00000",
            "note": "",
            "advice": null,
            "createdby": "1",
            "timecreated": "146711713".$id
        ];
    }

    private function constructFinalStatus($id) {
        return (object) [
            "id": $id,
            "signupid": "4",
            "statuscode": "100",
            "superceded": "1",
            "grade": "100.00000",
            "note": "",
            "advice": null,
            "createdby": "1",
            "timecreated": "146711713".$id
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
