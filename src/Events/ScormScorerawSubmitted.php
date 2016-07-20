<?php namespace MXTranslator\Events;

class ScormScorerawSubmitted extends ModuleViewed {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override ModuleViewed
     */
    public function read(array $opts) {
        $scorm_score_max = $opts['scorm_scoes_track']['scoremax'];
        $scorm_score_raw = $opts['cmi_data']['cmivalue'];
        $scorm_score_min = $opts['scorm_scoes_track']['scoremin'];
        $scorm_score_scaled = NULL;

        if ($scorm_score_raw >= 0) {
            $scorm_score_scaled = $scorm_score_raw / $scorm_score_max;
        } else {
            $scorm_score_scaled = $scorm_score_raw / $scorm_score_min;
        }

        return [array_merge(parent::read($opts)[0], [
            'recipe' => 'scorm_scoreraw_submitted',
            'scorm_url' => $opts['module']->url,
            'scorm_name' => $opts['module']->name,
            'scorm_attempt' => $opts['cmi_data']['attemptid'],
            'scorm_score_raw' => $scorm_score_raw,
            'scorm_score_min' => $scorm_score_min,
            'scorm_score_max' => $scorm_score_max,
            'scorm_score_scaled' => $scorm_score_scaled,
            'scorm_status' => $opts['scorm_scoes_track']['status'],
        ])];
    }
}
