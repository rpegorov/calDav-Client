<?php

namespace Api\V2\Models\Calendar;

class ICalendarHeader {
    private $prodId;
    private $version;

    public function __construct($prodId, $version) {
        $this->prodId  = $prodId;
        $this->version = $version;
    }

    public function toString(): string {
        $headerString = "BEGIN:VCALENDAR\n";
        $headerString .= "PRODID:".$this->prodId."\n";
        $headerString .= "VERSION:".$this->version."\n";

        return $headerString;
    }
}