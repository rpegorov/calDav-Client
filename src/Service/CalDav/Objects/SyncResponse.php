<?php

namespace Services\CalDav\Objects;

class SyncResponse implements DeserializeInterface
{
    /**
     *@var SyncEvent[] $response
     */
    public $response = [];
}