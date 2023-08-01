<?php

namespace Api\V2\Models\Calendar;

class ICalendarDataModel {
    private int $id;
    private int $activityId;
    private string $href;
    private ?string $etag;
    private string $uid;
    private string $owner;


    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param   int  $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getActivityId(): int {
        return $this->activityId;
    }

    /**
     * @param   int  $syncToken
     */
    public function setActivityId(int $activityId): void {
        $this->activityId = $activityId;
    }

    /**
     * @return string
     */
    public function getHref(): string {
        return $this->href;
    }

    /**
     * @param   string  $href
     */
    public function setHref(string $href): void {
        $this->href = $href;
    }

    /**
     * @return string
     */
    public function getEtag(): ?string {
        return $this->etag;
    }

    /**
     * @param   string|null  $etag
     */
    public function setEtag(?string $etag): void {
        $this->etag = $etag;
    }

    /**
     * @return string
     */
    public function getUid(): string {
        return $this->uid;
    }

    /**
     * @param   string  $uid
     */
    public function setUid(string $uid): void {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getOwner(): string {
        return $this->owner;
    }

    /**
     * @param   string  $owner
     */
    public function setOwner(string $owner): void {
        $this->owner = $owner;
    }



}