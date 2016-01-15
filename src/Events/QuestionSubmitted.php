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

        $translatorevent = [
            'recipe' => 'attempt_question_completed'
        ];

        //The attempt extension for the attempt includes all question data. 
        //For questions, only include data relevant to the current question. 
        $template['attempt_ext']->questions = [$questionAttempt];
        $translatorevent['attempt_ext'] = $template['attempt_ext'];
        $translatorevent['question_attempt_ext'] = $questionAttempt;
        $translatorevent['question_attempt_ext_key'] = 'http://lrs.learninglocker.net/define/extensions/moodle_question_attempt';
        $translatorevent['question_ext'] = $question;
        $translatorevent['question_ext_key'] = 'http://lrs.learninglocker.net/define/extensions/moodle_question';

        $translatorevent['question_name'] = $question->name ?: 'A Moodle quiz question';
        $translatorevent['question_description'] = strip_tags($question->questiontext) ?: 'A Moodle quiz question';

        //scaled and raw score default is zero;
        $translatorevent['attempt_score_scaled'] = 0;
        $translatorevent['attempt_score_raw'] = 0;
        //minimum score is always 0
        $translatorevent['attempt_score_min'] = 0;
        $translatorevent['attempt_score_max'] = $questionAttempt->maxmark;

        $submittedState = $this->getLastState($questionAttempt);

        if (!is_null($submittedState->timestamp)) {
            $translatorevent['time'] = date('c', $submittedState->timestamp);
        }

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

        //default response if it can't be modelled
        $translatorevent['attempt_response'] = $questionAttempt->responsesummary;

        //Due to the infinite nature of Moodle question types, determine xAPI question type based on
        //the available question data, rather than the type declared in $question->qtype
        //First, see if it's possible to model the question as a 'choice'. 
        if (!is_null($question->answers) && ($question->answers !== [])) {
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

            //true-false is basically a special case of choice
            $trueWords = ['true', 'yes', 'y', 'right', 'correct', 'agree'];
            $falseWords = ['false', 'no', 'n', 'wrong', 'incorrect', 'disagree'];
            $lowerCaseChoices = array_map('strtolower', $choices);
            if (
                count($choices) == 2
                && (count(array_intersect($trueWords, $lowerCaseChoices)) == 1)
                && (count(array_intersect($falseWords, $lowerCaseChoices)) == 1)
            ) {
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
        }
        else {
            //other question type
            $translatorevent['interaction_type'] = "other";
        }

        return array_merge($template, $translatorevent);
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