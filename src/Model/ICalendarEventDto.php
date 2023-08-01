<?php

namespace Api\V2\Models\Calendar;

use Bitrix\Main\Type\DateTime;

class ICalendarEventDto {
    private DateTime $dateCreated;
    private DateTime $dateModified;
    private DateTime $dateStart;
    private DateTime $dateEnd;
    private DateTime $dateStamp;
    private string $uid;
    private string $summary;
    private string $location;
    private string $description;
    private string $ownerId;
    private int $activityId;
    private int $candidateId;

    /**
     * @return int
     */
    public function getCandidateId(): int {
        return $this->candidateId;
    }

    /**
     * @param   int  $candidateId
     */
    public function setCandidateId(int $candidateId): void {
        $this->candidateId = $candidateId;
    }

    /**
     * @return int
     */
    public function getActivityId(): int {
        return $this->activityId;
    }

    /**
     * @param   int  $activityId
     */
    public function setActivityId(int $activityId): void {
        $this->activityId = $activityId;
    }

    /**
     * @return string
     */
    public function getOwnerId(): string {
        return $this->ownerId;
    }

    /**
     * @param   string  $ownerId
     */
    public function setOwnerId(string $ownerId): void {
        $this->ownerId = $ownerId;
    }

    public function getDateCreated(): string {
        return $this->dateCreated->format('Ymd\THis');
    }

    /**
     * @param   \Bitrix\Main\Type\DateTime  $dateCreated
     */
    public function setDateCreated(DateTime $dateCreated): void {
        $this->dateCreated = $dateCreated;
    }

    public function getDateModified(): string {
        return $this->dateModified->format('Ymd\THis');
    }

    /**
     * @param   \Bitrix\Main\Type\DateTime  $dateModified
     */
    public function setDateModified(DateTime $dateModified): void {
        $this->dateModified = $dateModified;
    }

    public function getDateStart(): string {
        return $this->dateStart->format('Ymd\THis');
    }

    /**
     * @param   \Bitrix\Main\Type\DateTime  $dateStart
     */
    public function setDateStart(DateTime $dateStart): void {
        $this->dateStart = $dateStart;
    }

    public function getDateEnd(): string {
        return $this->dateEnd->format('Ymd\THis');
    }

    /**
     * @param   \Bitrix\Main\Type\DateTime  $dateEnd
     */
    public function setDateEnd(DateTime $dateEnd): void {
        $this->dateEnd = $dateEnd;
    }

    public function getDateStamp(): string {
        return $this->dateStamp->format('Ymd\THis');
    }

    /**
     * @param   \Bitrix\Main\Type\DateTime  $dateStamp
     */
    public function setDateStamp(DateTime $dateStamp): void {
        $this->dateStamp = $dateStamp;
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
    public function getSummary(): string {
        return $this->summary;
    }

    /**
     * @param   string  $summary
     */
    public function setSummary(string $summary): void {
        $this->summary = $summary;
    }

    /**
     * @return string
     */
    public function getLocation(): string {
        return $this->location;
    }

    /**
     * @param   string  $location
     */
    public function setLocation(string $location): void {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @param   string  $description
     */
    public function setDescription(string $description): void {
        $this->description = $description;
    }


}