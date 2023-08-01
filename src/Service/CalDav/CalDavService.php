<?php

namespace Services\CalDav;

use Api\V2\Models\Calendar\ICalendar;
use Api\V2\Models\Calendar\ICalendarEventDto;
use Api\V2\Models\Calendar\ICalendarDataModel;
use Api\V2\Models\Calendar\ICalendarEvent;
use Api\V2\Models\Calendar\ICalendarHeader;
use Api\V2\Models\Calendar\ICalendarPropertySet;
use Api\V2\Repositories\Candidate\CandidateRepository;
use Api\V2\Repositories\ICalendar\ICalendarSettingsRepository;
use Api\V2\Repositories\ICalendar\ICalendarDataRepository;
use ITControllers\Calendar\Models\DeleteEventFilter;
use Services\ClassifierService;

class CalDavService extends ClassifierService {
    private $calendarSettingsRepository;
    private $calendarDataRepository;
    private $candidateRepository;
    private $modelMapper;
    /**
     * @var \Services\CalDav\CalDavFacade
     */
    private $client;

    public function __construct(
        ICalendarSettingsRepository $calendarSettingsRepository,
        ICalendarDataRepository $calendarDataRepository,
        CandidateRepository $candidateRepository,
        CalDavModelMapper $modelMapper
    ) {
        $this->calendarSettingsRepository = $calendarSettingsRepository;
        $this->calendarDataRepository     = $calendarDataRepository;
        $this->candidateRepository        = $candidateRepository;
        $this->modelMapper                = $modelMapper;

    }

    /**
     * @throws \Services\CalDav\CalDAVException
     */
    public function createCalendar(int $userId, string $name, string $displayName): bool {
        $this->setConnection($userId);

        return $this->client->createCalendar($name, $displayName);
    }

    /**
     * @throws \Services\CalDav\CalDAVException
     */
    public function findCalendar(string $userId): Objects\DeserializeInterface {
        $this->setConnection($userId);

        return $this->client->findCalendars();
    }

    /**
     * @throws \Services\CalDav\CalDAVException
     */
    public function getEvent(string $uuid, $userId): Objects\DeserializeInterface {
        $this->setConnection($userId);

        return $this->client->getEvent($uuid);
    }

    /**
     * @throws \Exception
     */
    public function createEvent($event) {
        $eventDto = $this->prepareEventModel($event);
        $calEvent = $this->prepareEvent($eventDto);
        $connect = $this->setConnection($eventDto->getOwnerId());
        if(is_null($connect)) {
            return;
        }
        $updateData = $this->checkCreateOrUpdate($eventDto->getActivityId());
        if(is_null($updateData)) {
            $calDavObject = $this->client->create($calEvent);
            $dataModel = $this->prepareDataModel($eventDto, $calDavObject);
            $this->calendarDataRepository->add($dataModel);
        } else {
            $this->updateEvent($event, $updateData);
        }
    }

    /**
     * @throws \Services\CalDav\CalDAVException
     * @throws \Exception
     */
    private function updateEvent($event, $updateData) {
        $eventDto = $this->prepareUpdateEventModel($event, $updateData);
        $calEvent = $this->prepareEvent($eventDto);

        $connect = $this->setConnection($eventDto->getOwnerId());
        if(is_null($connect)) {
            return;
        }
        $calDavObject = $this->client->update($calEvent);
        $dataModel    = $this->prepareDataModel($eventDto, $calDavObject);
        $this->calendarDataRepository->add($dataModel);
    }

    /**
     * @throws \Exception
     */
    public function deleteEvent($deleteEvent) {
        $eventId      = $deleteEvent->getId();
        $ownerId      = $deleteEvent->getUfOwner();
        $calendarData = $this->calendarDataRepository->fetchByOwnerIdAndEventId($ownerId, $eventId);
        $href         = $calendarData->getHref();
        $etag         = $calendarData->getEtag();

        $this->setConnection($ownerId);

        $this->client->delete($href, $etag);

        $this->calendarDataRepository->deleteById($calendarData->getid());
    }

    private function checkCreateOrUpdate($activityId): ?ICalendarDataModel {
        return $this->calendarDataRepository->findByActivityId($activityId);
    }

    /**
     * @var \Services\CalDav\Objects\CalendarsList $calendars
     *
     * @param   int                                $userId
     *
     * @return \Services\CalDav\CalDavFacade|null
     * @throws \Services\CalDav\CalDAVException
     */
    private function setConnection(int $userId): ?CalDavFacade {
        $this->client = new CalDavFacade();
        $userSettings = $this->calendarSettingsRepository->fetchSettingsByOwner($userId);
        if(is_null($userSettings)) {
            return null;
        }
        $this->client->connect(
            $userSettings->getBaseUrl(),
            $userSettings->getUserLogin(),
            $userSettings->getPassword()
        );
        $calendars = $this->client->findCalendars();
        $calendar  = $calendars->find($userSettings->getCalendarName());
        $this->client->setCalendar($calendar);

        return $this->client;
    }

    public function syncCalendars($userId) {
        $userSettings = $this->calendarSettingsRepository->fetchSettingsByOwner($userId);
        $client = $this->setConnection($userId);
        if(is_null($client)) {
            return;
        }
        $calendars = $this->client->findCalendars();
        $calendar = $calendars->find($userSettings->getCalendarName());
        $syncToken = $calendar->syncToken;
        $sync = $this->client->syncCalendar($syncToken);
    }

    private function prepareEventModel($event): ICalendarEventDto {
        return $this->modelMapper->eventModelToDto($event);
    }

    private function prepareUpdateEventModel($event, $updateData): ICalendarEventDto {
        return $this->modelMapper->eventUpdateModelToDto($event, $updateData);
    }

    private function prepareDataModel(ICalendarEventDto $eventDto, CalDAVObject $caldavObject): ICalendarDataModel {
        return $this->modelMapper->dataDtoToModel($eventDto, $caldavObject);
    }

    /**
     * @param   \Api\V2\Models\Calendar\ICalendarEventDto  $dto
     *
     * @return string
     */
    private function prepareEvent(ICalendarEventDto $dto): string {
        $candidateId = $dto->getCandidateId();
        $candidateInfo = $this->candidateRepository->findCandidateInfo($candidateId);
        $candidateLink = $_SERVER['HTTP_ORIGIN']."/candidates/"."$candidateId";

        $header = new ICalendarHeader("-//hrCrm//RU", "2.0");
        $calendar = new ICalendar($header);
        $eventProperties = new ICalendarPropertySet();
        $eventProperties->setProperty("SUMMARY", $dto->getSummary());
        $eventProperties->setProperty("UID", $dto->getUid());
        $eventProperties->setProperty("DTSTART", $dto->getDateStart());
        $eventProperties->setProperty("DTEND", $dto->getDateEnd());
        $eventProperties->setProperty("DTSTAMP", $dto->getDateStamp());
        $eventProperties->setProperty("CREATED", $dto->getDateCreated());
        $eventProperties->setProperty("LAST-MODIFIED", $dto->getDateModified());
        $eventProperties->setProperty("LOCATION", $dto->getLocation());
        $eventProperties->setProperty("DESCRIPTION", $candidateInfo === null ? $dto->getDescription(). " " : $dto->getDescription()." ".$candidateInfo->toString()." "."ссылка на кандидата $candidateLink");
        $event = new ICalendarEvent($eventProperties);
        $calendar->addEvent($event);
        return $calendar->toString();
    }
}