<?php

namespace Api\V2\Repositories\ICalendar;

use Api\V2\Models\Calendar\ICalendarDataModel;
use Api\V2\Repositories\ServiceRepository;
use HBExtends\ICalendar\ICalendarDataTable;
use Bitrix\Main\ORM\Query;

class ICalendarDataRepository extends ServiceRepository {

    /**
     * @param   \Api\V2\Models\Calendar\ICalendarDataModel  $model
     *
     * @return void
     */
    public function add($model) {
        $messageDb = ICalendarDataTable::createObject()
            ->setUfActivityId($model->getActivityId())
            ->setUfHref($model->getHref())
            ->setUfEtag($model->getEtag())
            ->setUfUid($model->getUid())
            ->setUfOwner($model->getOwner());
        $messageDb->save();
    }

    /**
     * @param   \Api\V2\Models\Calendar\ICalendarDataModel  $model
     *
     * @return void
     */
    public function update($model, $id) {
        foreach($model as $item)
            ICalendarDataTable::update($item['ID'], $item);
    }

    public function getAll(): ?ICalendarDataModel {
        $search = ICalendarDataTable::query()
            ->setSelect(['*'])
            ->fetchAll();
        if (empty($search)) {
            return null;
        }
        $model = new ICalendarDataModel();
        $model->setActivityId($search['UF_ACTIVITY_ID']);
        $model->setHref($search['UF_HREF']);
        $model->setEtag($search['UF_ETAG']);
        $model->setUid($search['UF_UID']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }

    public function getById(int $id) {
        $search = ICalendarDataTable::query()
            ->setSelect(['*'])
            ->where('ID', $id)
            ->fetchAll();
        if (empty($search)) {
            return null;
        }
        $model = new ICalendarDataModel();
        $model->setActivityId($search['UF_ACTIVITY_ID']);
        $model->setHref($search['UF_HREF']);
        $model->setEtag($search['UF_ETAG']);
        $model->setUid($search['UF_UID']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }

    public function deleteAll() {
        $search = ICalendarDataTable::query()
            ->setSelect(['*'])
            ->fetchAll();
        foreach($search as $item) {
            ICalendarDataTable::delete($item['ID']);
        }
    }

    public function deleteById(int $id) {
        ICalendarDataTable::delete($id);
    }

    public function fetchByOwnerIdAndEventId($ownerId, $eventId): ?ICalendarDataModel {
        $search = ICalendarDataTable::query()
            ->setSelect(['ID', 'UF_HREF', 'UF_ETAG'])
            ->where(Query\Query::filter()
                ->logic('and')
                ->where('UF_OWNER', $ownerId)
                ->where('UF_ACTIVITY_ID', $eventId)
            )
            ->fetch();
        if (is_null($search)) {
            return null;
        }
        $model = new ICalendarDataModel();
        $model->setId($search['ID']);
        $model->setHref($search['UF_HREF']);
        $model->setEtag($search['UF_ETAG']);
        return $model;
    }

    public function fetchByOwnerIdAndUid(int $ownerId, string $uid): ?ICalendarDataModel {
        $search = ICalendarDataTable::query()
            ->setSelect(['*'])
            ->where(Query\Query::filter()
                ->logic('and')
                ->where('UF_OWNER', $ownerId)
                ->where('UF_UID', $uid)
            )
            ->fetch();
        if (empty($search)) {
            return null;
        }
        $model = new ICalendarDataModel();
        $model->setActivityId($search['UF_ACTIVITY_ID']);
        $model->setHref($search['UF_HREF']);
        $model->setEtag($search['UF_ETAG']);
        $model->setUid($search['UF_UID']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }

    public function findByOwnerId($id): ?ICalendarDataModel {
        $search = ICalendarDataTable::query()
            ->setSelect(['*'])
                ->where('UF_OWNER', $id)
            ->fetchAll();
        if (empty($search)) {
            return null;
        }
        $model = new ICalendarDataModel();
        $model->setActivityId($search['ID']);
        $model->setHref($search['UF_HREF']);
        $model->setEtag($search['UF_ETAG']);
        $model->setUid($search['UF_UID']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }

    public function findByActivityId($activityId): ?ICalendarDataModel {
        $search = ICalendarDataTable::query()
            ->setSelect(['*'])
            ->where('UF_ACTIVITY_ID', $activityId)
            ->fetch();
        if (empty($search)) {
            return null;
        }
        $model = new ICalendarDataModel();
        $model->setActivityId($search['UF_ACTIVITY_ID']);
        $model->setHref($search['UF_HREF']);
        $model->setEtag($search['UF_ETAG']);
        $model->setUid($search['UF_UID']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }
}