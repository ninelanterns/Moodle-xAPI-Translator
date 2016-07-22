<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\ScormScorerawSubmitted as Event;

class ScormScorerawSubmittedTest extends ModuleViewedTest {
    protected static $recipe_name = 'scorm_scoreraw_submitted';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }

    protected function constructInput() {
        return array_merge(parent::constructInput(), [
            'scorm_scoes_track' => [
                'scoremax' => 100,
                'scoremin' => 0,
                'status' => 'status',
            ],
            'cmi_data' => [
                'cmivalue' => 0,
                'cmielement' => 'cmi.core.score.raw',
                'attemptid' => 2,
            ],
            'scorm_scoes' => [
                'id' => 1,
                'scorm' => 1,
                'scormtype' => 'sco',
            ]
        ]);
    }

    protected function assertOutput($input, $output) {
        parent::assertOutput($input, $output);
        $this->assertEquals($input['module']->name, $output['scorm_name']);
        $this->assertEquals($input['module']->url, $output['scorm_url']);
        $this->assertEquals($input['scorm_scoes_track']['scoremin'], $output['scorm_score_min']);
        $this->assertEquals($input['scorm_scoes_track']['scoremax'], $output['scorm_score_max']);
        $this->assertEquals($input['scorm_scoes_track']['status'], $output['scorm_status']);
        $this->assertEquals($input['cmi_data']['attemptid'], $output['scorm_attempt']);
    }
}
