<?php

namespace Agents;

use Api\V2\Repositories\Activity\ActivityRepository;
use Api\V2\Repositories\ICalendar\ICalendarDataRepository;
use Api\V2\Repositories\ICalendar\ICalendarSettingsRepository;
use Api\V2\Repositories\ICalendar\ICalendarSyncDataRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Services\CalDav\CalDAVException;
use Services\CalDav\CalDavFacade;
use Services\CalDav\Objects\Event;
use Services\CalDav\Objects\SyncEvent;
use Services\CurrentUserService;
use \Api\V2\Models\Calendar\UserSettingsModel;
use Api\V2\Models\Calendar\ICalendarDataModel;

function searchUserSettings($userService): ?array {
    $connectionSettings = new ICalendarSettingsRepository($userService);

    return $connectionSettings->getAll();
}

/**
 * @throws ObjectPropertyException
 * @throws SystemException
 * @throws ArgumentException
 */
function searchSyncToken($userService, $ownerId): string {
    $syncToken = new ICalendarSyncDataRepository($userService);

    return $syncToken->getByOwnerId($ownerId);
}

function fetchEvent($userService, $ownerId, $eventUid): ?ICalendarDataModel {
    $calendarData = new ICalendarDataRepository($userService);

    return $calendarData->fetchByOwnerIdAndUid($ownerId, $eventUid);
}

/**
 * @param $client CalDavFacade
 * @param $syncToken
 * @param $userService
 * @param $ownerId
 * @return void
 */
function getEventsListToUpdate(CalDavFacade $client, $syncToken, $userService, $ownerId) {
    /** @var SyncEvent[] $eventsList */
    $eventsList = $client->syncCalendar($syncToken);
    foreach($eventsList->response as $item) {
        $eventUuid = $item->uuid;
        if(!is_null(fetchEvent($userService, $ownerId, $eventUuid))) {
            continue;
        }
        if ($item->status !== 'HTTP/1.1 200 OK') {
            deleteEvents($userService, $ownerId, $eventUuid);
        }
        parseEvent($item, $client, $userService, $ownerId);
    }

}

/**
 * @param $event
 * @param $client CalDavFacade;
 * @param $userService
 * @param $ownerId
 * @return string[]
 */
function parseEvent($event, $client, $userService, $ownerId) {
    /** @var Event $event */
    $foundEvent = $client->getEvent($event->uuid);
    $eventData  = $foundEvent->response->data;

    $dtStartPattern     = '/DTSTART:(.*)\R/';
    $dtEndPattern       = '/DTEND:(.*)\R/';
    $locationPattern    = '/LOCATION:(.*)\R/';
    $summaryPattern     = '/SUMMARY:(.*)\R/';
    $descriptionPattern = '/DESCRIPTION:(.*)\R/';

    $data = [
        'DATE_START'   => extractValue($dtStartPattern, $eventData),
        'DATE_END'     => extractValue($dtEndPattern, $eventData),
        'MEET_PLACE'    => extractValue($locationPattern, $eventData),
        'MEET_SUBJECT'     => extractValue($summaryPattern, $eventData),
        'MEET_COMMENT' => extractValue($descriptionPattern, $eventData),
    ];
    createMeeting($userService, $ownerId, $data);
}

function extractValue($pattern, $inputData): string {
    if(preg_match($pattern, $inputData, $matches)) {
        return trim($matches[1]);
    }

    return 'Not found';
}


function createMeeting($userService, $ownerId, $data) {
    $activityRepos = new ActivityRepository($userService);
    \Debug::lm($data);
    $activityRepos->createMeeting($ownerId, $data);
}

function deleteEvents($userService, $ownerId, $eventUuid) {
    $activityId = fetchEvent($userService, $ownerId, $eventUuid);
    $activityRepos = new ActivityRepository($userService);
    $activityRepos->deleteActivityById($activityId->getActivityId());
}

function updateSyncToken($userService, $calendar, $ownerId) {
    $syncToken = $calendar->syncToken;
    $syncRepository = new ICalendarSyncDataRepository($userService);
    $syncRepository->updateToken($ownerId, $syncToken);
}

class ICalendarSyncAgent {
    /**
     * @return string
     * @throws ArgumentException
     * @throws CalDAVException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws \Exception
     */
    static public function syncCalendars(): string {
        $userService = new CurrentUserService();

        /** @var UserSettingsModel[] $userSettingsList */
        $userSettingsList = searchUserSettings($userService);
        $client           = new CalDavFacade();
        foreach($userSettingsList as $item) {
            $client->connect($item->getBaseUrl(), $item->getUserLogin(), $item->getPassword());
            $calendars = $client->findCalendars();
            $calendar  = $calendars->find($item->getCalendarName());
            $client->setCalendar($calendar);
            $syncToken = searchSyncToken($userService, $item->getOwner());
            getEventsListToUpdate($client, $syncToken, $userService, $item->getOwner());
            updateSyncToken($userService, $calendar,  $item->getOwner());
        }

        return "Agents\ICalendarSyncAgent::syncCalendars();";
    }
}