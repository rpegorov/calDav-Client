<?php

namespace Api\V2\Repositories\ICalendar;

use Api\V2\Repositories\ServiceRepository;
use HBExtends\ICalendar\ICalendarSyncDataTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\SystemException;

class ICalendarSyncDataRepository extends ServiceRepository {

    public function add($ownerId, $syncToken) {
        $messageDb = ICalendarSyncDataTable::createObject()
            ->setUfOwnerId($ownerId)
            ->setUfSyncToken($syncToken);
        $messageDb->save();
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getByOwnerId($ownerId): ?string {
        $search = ICalendarSyncDataTable::query()
            ->setSelect(['UF_SYNC_TOKEN'])
            ->where('UF_OWNER_ID', $ownerId)
            ->fetch();

        if (empty($search)) {
            return 'http://sabre.io/ns/sync/0';
        }
        return $search['UF_SYNC_TOKEN'];
    }

    public function updateToken($ownerId, $syncToken) {
        $obj = ICalendarSyncDataTable::query()
            ->setSelect(['ID', 'UF_OWNER_ID', 'UF_SYNC_TOKEN'])
            ->where('UF_OWNER_ID', $ownerId)
            ->fetchObject();
        if (is_null($obj)) {
            $this->add($ownerId, $syncToken);
        }
        $obj->setUfSyncToken($syncToken);
        $obj->save();
    }
}