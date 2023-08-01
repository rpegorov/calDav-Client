<?php

namespace Services\CalDav;

use Api\V2\Models\Calendar\ICalendarDataModel;
use Api\V2\Models\Calendar\ICalendarEventDto;
use Symfony\Component\Uid\Uuid;

class CalDavModelMapper {

    public function eventModelToDto($saveEvent): ICalendarEventDto {
        $eventDto = $this->prepareEventDto($saveEvent);
        $eventDto->setUid(Uuid::v4()->toRfc4122());

        return $eventDto;
    }

    /**
     * @param $saveEvent
     * @param $updateData \Api\V2\Models\Calendar\ICalendarDataModel
     *
     * @return \Api\V2\Models\Calendar\ICalendarEventDto
     */
    public function eventUpdateModelToDto($saveEvent, $updateData): ICalendarEventDto {
        $eventDto = $this->prepareEventDto($saveEvent);
        $eventDto->setUid($updateData->getUid());

        return $eventDto;
    }

    private function prepareEventDto($saveEvent): ICalendarEventDto {
        $settings        = unserialize($saveEvent->getUfSettings());
        $eventData       = $saveEvent->collectValues();
        $prepareEventDto = new ICalendarEventDto();
        $prepareEventDto->setDateCreated($eventData['UF_CREATED']);
        $prepareEventDto->setDateModified($eventData['UF_DATE_MODIFY']);
        $prepareEventDto->setDateStart($eventData['UF_START_TIME']);
        $prepareEventDto->setDateEnd($eventData['UF_END_TIME']);
        $prepareEventDto->setDateStamp($eventData['UF_CREATED']);
        $prepareEventDto->setSummary($settings['MEET_SUBJECT']);
        $prepareEventDto->setLocation(str_replace("\n", " ", $settings['MEET_PLACE']));
        $prepareEventDto->setDescription($settings['MEET_COMMENT']);
        $prepareEventDto->setOwnerId($eventData['UF_OWNER']);
        $prepareEventDto->setActivityId($eventData['ID']);
        $prepareEventDto->setCandidateId($eventData['UF_ENTITY_ID']);

        return $prepareEventDto;
    }

    public function dataDtoToModel(ICalendarEventDto $eventDto, CalDAVObject $calDavObject): ICalendarDataModel {
        $dataModel = new ICalendarDataModel();
        $dataModel->setActivityId($eventDto->getActivityId());
        $dataModel->setHref($calDavObject->getHref());
        $dataModel->setEtag($calDavObject->getEtag());
        $dataModel->setUid($eventDto->getUid());
        $dataModel->setOwner($eventDto->getOwnerId());

        return $dataModel;
    }
}