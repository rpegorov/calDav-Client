<?php

namespace Services\CalDav\Objects;

class Event
{
    /**
     *@var string $href
     */
    public $href;

    /**
     *@var string $data
     */
    public $data;
    /**
     *@var string $getetag
     */
    public $getetag;


    public function setPropstat(?array $props){


        if(!is_array($props['prop']) || $props['status'] !== 'HTTP/1.1 200 OK'){
            return;
        }
        $this->data = $props['prop']['cal:calendar-data'];
        $this->getetag = $props['prop']['getetag'];

    }
}