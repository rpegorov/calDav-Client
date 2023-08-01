<?php

namespace Services\CalDav\Objects;

class CalendarsList implements DeserializeInterface
{
    /**
     *@var Calendar[] $response
     */
    public $response = [];

    public function find($name):?Calendar{
        foreach ($this->response as $calendar){
            if($calendar->name === $name){
                return $calendar;
            }
        }
        return null;
    }
}