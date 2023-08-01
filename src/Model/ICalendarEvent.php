<?php

namespace Api\V2\Models\Calendar;

class ICalendarEvent {
    private $properties;

    public function __construct($properties) {
        $this->properties = $properties;
    }

    public function toString(): string {
        $eventString = "BEGIN:VEVENT\n";
        foreach($this->properties->getAllProperties() as $key => $value) {
            $eventString .= $key.":".$value."\n";
        }
        $eventString .= "END:VEVENT\n";

        return $eventString;
    }
}