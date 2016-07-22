<?php namespace MXTranslator\Events;

class ScormStatusSubmitted extends ModuleViewed {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override ModuleViewed
     */
    public function read(array $opts) {
        return [array_merge(parent::read($opts)[0], [
            'recipe' => 'scorm_status_submitted',
            'scorm_url' => $opts['module']->url,
            'scorm_name' => $opts['module']->name,
            'scorm_attempt' => $opts['cmi_data']['attemptid'],
            'scorm_status' => $opts['scorm_scoes_track']['status'],
        ])];
    }
}
