<?php

namespace Services\CalDav\Objects;

class Calendar
{
    /**
     *@var string $name
     */
    public $name;
    /**
     *@var string $href
     */
    public $href;

    /**
     *@var string $response
     */
    public $displayname;

    /**
     *@var string $syncToken
     */
    public $syncToken;

    public function setHref(string $href){
        $this->href = $href;
        $match= [];
        preg_match('#^\/calendars\/\w+\/([\w-]+)\/?#',$href,$match);
        $this->name =$match[1];
    }

    public function setPropstat(?array $props){
        if(!is_array($props)){
            return;
        }
        foreach ($props as $prop){
            if(!is_array($prop) || !is_array($prop['prop']) || $prop['status'] !== 'HTTP/1.1 200 OK'){
                continue;
            }
            $this->displayname = $prop['prop']['displayname'];
            $this->syncToken = $prop['prop']['cs:getctag'];
        }
    }

}