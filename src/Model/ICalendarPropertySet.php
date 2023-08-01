<?php

namespace Api\V2\Models\Calendar;

class ICalendarPropertySet {
    private $properties;

    public function __construct() {
        $this->properties = [];
    }

    public function setProperty($key, $value) {
        $this->properties[$key] = $value;
    }

    public function getProperty($key) {
        return isset($this->properties[$key]) ? $this->properties[$key] : null;
    }

    public function getAllProperties(): array {
        return $this->properties;
    }

    public function toString(): string {
        $propertyString = "";
        foreach($this->properties as $key => $value) {
            $propertyString .= $key.":".$value."\n";
        }

        return $propertyString;
    }
}