<?php namespace MXTranslator\Events;

class UserRegistered extends Event {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override Event
     */
    public function read(array $opts) {
        return array_merge(parent::read($opts), [
            'recipe' => 'user_registered',
        ]);
    }
}