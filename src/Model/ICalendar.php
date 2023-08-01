<?php

namespace Api\V2\Models\Calendar;


class ICalendar {
    private $header;
    private $events;

    public function __construct($header) {
        $this->header = $header;
        $this->events = [];
    }

    public function addEvent($event) {
        $this->events[] = $event;
    }

    public function toString(): string {
        $calendarString = $this->header->toString();
        foreach ($this->events as $event) {
            $calendarString .= $event->toString();
        }
        $calendarString .= "END:VCALENDAR";
        return $calendarString;
    }
}
