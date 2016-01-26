<?php namespace MXTranslator\Tests;
use \MXTranslator\Events\QuestionSubmitted as Event;

class QuestionSubmittedTest extends AttemptStartedTest {
    protected static $recipe_name = 'attempt_question_completed';

    /**
     * Sets up the tests.
     * @override TestCase
     */
    public function setup() {
        $this->event = new Event($this->repo);
    }

    protected function constructInput() {
        $input = array_merge(parent::constructInput(), [
            'questions' => $this->constructQuestions()
        ]);
        $input['attempt']->questions = $this->constructQuestionAttempts();

        return $input;
    }

    private function constructQuestionAttempts() {
        return [
            $this->constructQuestionAttempt(1),
            $this->constructQuestionAttempt(2),
            $this->constructQuestionAttempt(3)
        ];
    }

    private function constructQuestionAttempt($index) {
        return (object) [
            'id' => 1,
            'questionid' => 1,
            'maxmark' => '5.0000000',
            'steps' => [
                (object)[
                    'sequencenumber' => 1,
                    'state' => 'todo',
                    'timecreated' => '1433946000',
                    'fraction' => null
                ],
                (object)[
                    'sequencenumber' => 2,
                    'state' => 'gradedright',
                    'timecreated' => '1433946701',
                    'fraction' => '1.0000000'
                ],
            ],
            'responsesummary' => 'test answer',
            'rightanswer' => 'test answer'
        ];
    }

    private function constructQuestions() {
        return [
            $this->constructQuestion(1),
            $this->constructQuestion(2),
            $this->constructQuestion(3)
        ];
    }

    private function constructQuestion($index) {
        return (object) [
            'id' => 1,
            'name' => 'test question {$index}',
            'questiontext' => 'test questiontext',
            'url' => 'http://localhost/moodle/question/question.php?id=23',
            'answers' => [
                '1'=> (object)[
                    'id' => '1',
                    'answer' => 'test answer'
                ],
                '2'=> (object)[
                    'id' => '2',
                    'answer' => 'wrong test answer'
                ]
            ],
            'qtype' => 'multichoice'
        ];
    }

    protected function assertOutputs($input, $output) {
        //output is an associative array
        $this->assertEquals(0, count(array_filter(array_keys($output), 'is_string')));
        //length of output is 3.
        $this->assertEquals(3 , count($output));
    }

    protected function assertOutput($input, $output) {
        parent::assertOutput($input, $output);
        $this->assertAttempt($input['attempt'], $output);
        $this->assertQuestion($input['questions'], $output);
    }

    protected function assertAttempt($input, $output) {
        parent::assertAttempt($input, $output);
        $this->assertQuestionAttempt($input->questions, $output);
    }

    protected function assertQuestionAttempt($input, $output) {
        $this->assertEquals((float) $input[0]->maxmark, $output['attempt_score_max']);
        $this->assertEquals((float) $input[0]->steps[1]->fraction, $output['attempt_score_scaled']);
        $this->assertEquals((float) $input[0]->maxmark, $output['attempt_score_max']);
        $this->assertEquals('moodle_quiz_question_answer_1', $output['attempt_response']);
        $this->assertEquals('moodle_quiz_question_answer_1', $output['interaction_correct_responses']);
    }

    protected function assertQuestion($input, $output) {
        $this->assertEquals($input[0]->name, $output['question_name']);
        $this->assertEquals($input[0]->questiontext, $output['question_description']);
        $this->assertEquals($input[0]->answers['moodle_quiz_question_answer_2']->answer, $output['interaction_choices']['moodle_quiz_question_answer_2']);
    }
}
