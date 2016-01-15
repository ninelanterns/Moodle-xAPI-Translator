<?php namespace MXTranslator\Events;

class AttemptReviewed extends AttemptStarted {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override AttemtStarted
     */
    public function read(array $opts) {
        $translatorevents = [$this->attemptStatement($opts)];

        //push question statements to $translatorevents['events']
        foreach ($opts['attempt']->questions as $questionId => $questionAttempt) {
            array_push(
                $translatorevents, 
                $this->questionStatement(
                    parent::read($opts),
                    $questionAttempt,
                    $opts['questions'][$questionAttempt->questionid]
                )
            );
        }

        return $translatorevents;
    }

    protected function attemptStatement(array $opts) {
        $seconds = $opts['attempt']->timefinish - $opts['attempt']->timestart;
        $duration = "PT".(string) $seconds."S";
        $scoreRaw = (float) ($opts['attempt']->sumgrades ?: 0);
        $scoreMin = (float) ($opts['grade_items']->grademin ?: 0);
        $scoreMax = (float) ($opts['grade_items']->grademax ?: 0);
        $scorePass = (float) ($opts['grade_items']->gradepass ?: null);
        $success = false;
        //if there is no passing score then success is unknown.
        if ($scorePass == null) {
            $success = null;
        }
        elseif ($scoreRaw >= $scorePass) {
            $success = true;
        }
        //Calculate scaled score as the distance from zero towards the max (or min for negative scores).
        $scoreScaled;
        if ($scoreRaw >= 0) {
            $scoreScaled = $scoreRaw / $scoreMax;
        }
        else
        {
            $scoreScaled = $scoreRaw / $scoreMin;
        }
        return array_merge(parent::read($opts), [
            'recipe' => 'attempt_completed',
            'attempt_score_raw' => $scoreRaw,
            'attempt_score_min' => $scoreMin,
            'attempt_score_max' => $scoreMax,
            'attempt_score_scaled' => $scoreScaled,
            'attempt_success' => $success,
            'attempt_completed' => $opts['attempt']->state === 'finished',
            'attempt_duration' => $duration,
        ]);
    }

    protected function questionStatement($template, $questionAttempt, $question) {

        $translatorevent = 
            'recipe' => 'attempt_question_completed'
        ]

        //scaled and raw score default is zero 
        $translatorevent['attempt_score_scaled'] = 0;
        $translatorevent['attempt_score_raw'] = 0;
        //minimum score is always 0
        $translatorevent['attempt_score_min'] = 0;
        $translatorevent['attempt_score_max'] = $questionAttempt->maxmark;


        $submittedState = getLastState($questionAttempt);

        if (!is_null($submittedState->timestamp)){
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
                $translatorevent['attempt_success'] = false;
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

        //calulcate response by comparing $questionAttempt->responsesummary; to the possible answers to get the id
        $choices = [];
        foreach ($question as $answerId => $answer) {
            $choices[$answerId] = strip_tags($answer->answer);
        }

        return array_merge($template, $translatorevent);
    }

    private function getLastState($questionAttempt) {
        $sequencenumber = -1
        $state = (object)[
            "state" => "todo",
            "timestamp" => null
        ];
        foreach ($questionAttempt->steps as $stepId => $step) {
            if ($step->sequencenumber > $sequencenumber){
                $sequencenumber = $step->sequencenumber;
                $state = (object)[
                    "state" => $step->state,
                    "timestamp" => $step->timestamp,
                    "fraction" => $step->fraction
                ];
            } 
        }
    }
}