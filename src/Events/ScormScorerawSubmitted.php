<?php namespace MXTranslator\Events;

class ScormScorerawSubmitted extends ModuleViewed {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override ModuleViewed
     */
    public function read(array $opts) {
        $scoreMax = $opts['scorm_scoes_track']['scoremax'];
        $scoreRaw = $opts['cmi_data']['cmivalue'];
        $scoreMin = $opts['scorm_scoes_track']['scoremin'];
        $scoreScaled = NULL;

        $scoreScaled = $scoreRaw >= 0 ? ($scoreRaw / $scoreMax) : ($scoreRaw / $scoreMin);

        return [array_merge(parent::read($opts)[0], [
            'recipe' => 'scorm_scoreraw_submitted',
            'scorm_url' => $opts['module']->url,
            'scorm_name' => $opts['module']->name,
            'scorm_scoes_id' => $opts['scorm_scoes']->id,
            'scorm_scoes_type' => 'http://adlnet.gov/expapi/activities/lesson',
            'scorm_scoes_url' => $opts['module']->url,
            'scorm_scoes_name' => $opts['scorm_scoes']->title,
            'scorm_scoes_description' => $opts['scorm_scoes']->title,
            'scorm_attempt' => $opts['cmi_data']['attemptid'],
            'scorm_score_raw' => $scoreRaw,
            'scorm_score_min' => $scoreMin,
            'scorm_score_max' => $scoreMax,
            'scorm_score_scaled' => $scoreScaled,
            'scorm_status' => $opts['scorm_scoes_track']['status'],
        ])];
    }
}
