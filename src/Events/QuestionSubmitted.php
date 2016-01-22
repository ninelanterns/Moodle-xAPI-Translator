<?php namespace MXTranslator\Events;

class QuestionSubmitted extends AttemptStarted {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override AttemtStarted
     */
    public function read(array $opts) {
        $translatorevents = [];

        //push question statements to $translatorevents['events']
        foreach ($opts['attempt']->questions as $questionId => $questionAttempt) {
            array_push(
                $translatorevents,
                $this->questionStatement(
                    parent::read($opts)[0],
                    $questionAttempt,
                    $opts['questions'][$questionAttempt->questionid]
                )
            );
        }

        return $translatorevents;
    }

    protected function questionStatement($template, $questionAttempt, $question) {

        //The attempt extension for the attempt includes all question data. 
        //For questions, only include data relevant to the current question. 
        $template['attempt_ext']->questions = [$questionAttempt];

        $translatorevent = [
            'recipe' => 'attempt_question_completed',
            'attempt_ext' => $template['attempt_ext'],
            'question_attempt_ext' => $questionAttempt,
            'question_attempt_ext_key' => 'http://lrs.learninglocker.net/define/extensions/moodle_question_attempt',
            'question_ext' => $question,
            'question_ext_key' => 'http://lrs.learninglocker.net/define/extensions/moodle_question',
            'question_name' => $question->name ?: 'A Moodle quiz question',
            'question_description' => strip_tags($question->questiontext) ?: 'A Moodle quiz question',
            'attempt_score_scaled' => 0, //default
            'attempt_score_raw' => 0, //default
            'attempt_score_min' => 0, //always 0
            'attempt_score_max' => $questionAttempt->maxmark,
            'attempt_response' => $questionAttempt->responsesummary, //default
        ];

        $submittedState = $this->getLastState($questionAttempt);

        if (!is_null($submittedState->timestamp)) {
            $translatorevent['time'] = date('c', $submittedState->timestamp);
        }

        $translatorevent = $this->resultFromState ($translatorevent, $submittedState);

        //Due to the infinite nature of Moodle question types, determine xAPI question type based on
        //the available question data, rather than the type declared in $question->qtype
        //First, see if it's possible to model the question as a 'choice' (or 'truefalse'). 
        if (!is_null($question->answers) && ($question->answers !== [])) {
            $translatorevent = $this->multichoiceStatement ($translatorevent, $questionAttempt, $question);
        }
        else {
            //other question type
            $translatorevent['interaction_type'] = "other";
        }

        return array_merge($template, $translatorevent);
    }

    public function resultFromState ($translatorevent, $submittedState){
        switch ($submittedState->state) {
            case "todo":
                $translatorevent['attempt_completed'] = false;
                $translatorevent['attempt_success'] = null;
                break;
            case "gaveup":
                $translatorevent['attempt_completed'] = false;
                $translatorevent['attempt_success'] = false;
                break;
            case "complete":
                $translatorevent['attempt_completed'] = true;
                $translatorevent['attempt_success'] = null;
                break;
            case "gradedwrong":
                $translatorevent['attempt_completed'] = true;
                $translatorevent['attempt_success'] = false;
                $translatorevent['attempt_score_scaled'] = $submittedState->fraction;
                $translatorevent['attempt_score_raw'] = $submittedState->fraction * $questionAttempt->maxmark;
                break;
            case "gradedpartial":
                $translatorevent['attempt_completed'] = true;
                $translatorevent['attempt_success'] = false;
                $translatorevent['attempt_score_scaled'] = $submittedState->fraction;
                $translatorevent['attempt_score_raw'] = $submittedState->fraction * $questionAttempt->maxmark;
                break;
            case "gradedright":
                $translatorevent['attempt_completed'] = true;
                $translatorevent['attempt_success'] = true;
                $translatorevent['attempt_score_scaled'] = $submittedState->fraction;
                $translatorevent['attempt_score_raw'] = $submittedState->fraction * $questionAttempt->maxmark;
                break;
            default:
                $translatorevent['attempt_completed'] = null;
                $translatorevent['attempt_success'] = null;
                break;
        }

        return $translatorevent;
    }

    public function multichoiceStatement ($translatorevent, $questionAttempt, $question){
        $choices = [];
        foreach ($question->answers as $answerId => $answer) {
            $choices[$answerId] = strip_tags($answer->answer);
        }
        //If there are answers, assume multiple choice until proven otherwise
        $translatorevent['interaction_type'] = 'choice';
        $translatorevent['interaction_choices'] = $choices;

        $responses = [];
        //We can't simply explode $questionAttempt->responsesummary using "; " as the delimiter
        //because responses may contain the string "; ". 
        foreach ($choices as $answerId => $choice) {
            if (!(strpos($questionAttempt->responsesummary, $choice) === false)) {
                array_push($responses, $answerId);
            }
        }
        $translatorevent['attempt_response'] = implode('[,]', $responses);

        $correctResponses = [];
        foreach ($choices as $answerId => $choice) {
            if (!(strpos($questionAttempt->rightanswer, $choice) === false)) {
                array_push($correctResponses, $answerId);
            }
        }
        $translatorevent['interaction_correct_responses'] = [implode('[,]', $correctResponses)];

        //special handling of true-false question type (some overlap with multichoice)
        if ($question->qtype == 'truefalse') {
            $translatorevent['interaction_type'] = "true-false";
            $translatorevent['interaction_choices'] = null;

            if (in_array(strtolower($questionAttempt->responsesummary), $trueWords)) {
                $translatorevent['attempt_response'] = "true";
            }
            elseif (in_array(strtolower($questionAttempt->responsesummary), $falseWords)) {
                $translatorevent['attempt_response'] = "false";
            }

            if (in_array(strtolower($questionAttempt->rightanswer), $trueWords)) {
                $translatorevent['interaction_correct_responses'] = ["true"];
            }
            elseif (in_array(strtolower($questionAttempt->rightanswer), $falseWords)) {
                $translatorevent['interaction_correct_responses'] = ["false"];
            }
        }
        return $translatorevent;
    }

    private function getLastState($questionAttempt){
        $sequencenumber = -1;
        $state = (object)[
            "state" => "todo",
            "timestamp" => null
        ];
        foreach ($questionAttempt->steps as $stepId => $step) {
            if ($step->sequencenumber > $sequencenumber) {
                $sequencenumber = $step->sequencenumber;
                $state = (object)[
                    "state" => $step->state,
                    "timestamp" => $step->timecreated,
                    "fraction" => $step->fraction
                ];
            }
        }
        return $state;
    }
}