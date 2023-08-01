<?php

namespace Services\CalDav\Objects;

class SyncEvent
{
    /**
     *@var string $href
     */
    public $href;

    /**
     *@var string $getetag
     */
    public $getetag;

    /**
     *@var string $status
     */
    public $status;
    /**
     *@var string $uuid
     */
    public $uuid;

    public function setHref(string $href){
        $this->href = $href;
        $match= [];
        preg_match('#^\/calendars\/[\w\_-]+\/[\w_-]+\/([\w-]+)\.ics#',$href,$match);
        $this->uuid =$match[1];
    }
    public function setPropstat(?array $props){


        if(!is_array($props['prop'])){
            return;
        }
        $this->getetag = $props['prop']['getetag'];
        if(is_null($this->status))
        $this->status = $props['status'];

    }
}