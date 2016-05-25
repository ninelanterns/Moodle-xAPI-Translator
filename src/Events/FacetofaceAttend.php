<?php namespace MXTranslator\Events;

class FacetofaceAttend extends FacetofaceEnrol {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override AttemtStarted
     */
    public function read(array $opts) {
        $translatorevents = [];

        $statuscodes = (object)[
            'attended' => 100,
            'partial' => 90
        ]
        $partialAttendanceDurationCredit = 0.5;

        $sessionDuration = 0;
        foreach ($opts['session']->dates as $index => $date) {
            $sessionDuration -= $date->timestart;
            $sessionDuration += $date->timefinish;
        }

        foreach ($opts['signups'] as $signupIndex => $signup) {

            $currentStatus = null;
            $previousAttendance = false;
            $previousPartialAttendance = false;
            foreach ($signup->statuses as $statusIndex => $status) {
                if ($status->timecreated == $opts['event']['timecreated']) {
                    $currentStatus = $status;
                    continue;
                }
                if ($status->timecreated < $opts['event']['timecreated'] && $status->statuscodestatus == $statuscodes->partial) {
                    $previousPartialAttendance = true;
                    continue;
                }
                if ($status->timecreated < $opts['event']['timecreated'] && $status->statuscodestatus == $statuscodes->attended) {
                    $previousAttendance = true;
                    continue;
                }
            }

            if (is_null($currentStatus)){
                continue;
            }

            $duration = null;
            $completion = null;
            if $currentStatus->statuscodestatus == $statuscodes->attended){
                if ($previousAttendance == true){
                    // Attendance has already been recorded for this user and session
                    continue;
                }
                $duration = $sessionDuration;
                $completion = true;
            }
            else if $currentStatus->statuscodestatus == $statuscodes->partial){
                if ($previousPartialAttendance == true){
                    // Partial attendance has already been recorded for this user and session
                    continue;
                }
                $duration = $sessionDuration * $partialAttendanceDurationCredit;
                $completion = false;
            }
            else {
                continue;
            }

            array_push(
                $translatorevents,
                array_merge(parent::read($opts)[0], [
                    'recipe' => 'training_session_attend',
                    'attendee_id' => $signup['attendee']->id,
                    'attendee_url' => $signup['attendee']->url,
                    'attendee_name' => $opts['attendee']->fullname,
                    'attempt_duration' => "PT".(string) $duration."S";
                    'attempt_completion' => $completion;
                ])
            );
        }

        return $translatorevents;
    }
}