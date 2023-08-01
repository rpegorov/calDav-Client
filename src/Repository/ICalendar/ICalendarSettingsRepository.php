<?php

namespace Api\V2\Repositories\ICalendar;

use Api\V2\Repositories\ServiceRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\SystemException;
use HBExtends\ICalendar\ICalendarSettingsTable;
use Api\V2\Models\Calendar\UserSettingsModel;

class ICalendarSettingsRepository extends ServiceRepository {


    /**
     * @param UserSettingsModel $model
     *
     * @return void
     */
    public function add($model) {
       $messageDb = ICalendarSettingsTable::createObject()
           ->setUfBaseUrl($model->getBaseUrl())
           ->setUfCalendarName($model->getCalendarName())
           ->setUfUserLogin($model->getUserLogin())
           ->setUfUserPasswor($model->getPassword())
           ->setUfOwner($model->getOwner());
       $messageDb->save();
    }

    /**
     * @param UserSettingsModel $model
     * @return void
     * @throws \Exception
     */
    public function update($model, $id) {
        foreach($model as $item)
        ICalendarSettingsTable::update($item['ID'], $item);
    }

    public function getAll(): ?array {
        $search = ICalendarSettingsTable::query()
            ->setSelect(['*'])
            ->fetchAll();
        if (empty($search)) {
            return null;
        }
        $models = [];
        foreach($search as $item) {
            $model = new UserSettingsModel();
            $model->setBaseUrl($item['UF_BASE_URL']);
            $model->setCalendarName($item['UF_CALENDAR_NAME']);
            $model->setUserLogin($item['UF_USER_LOGIN']);
            $model->setPassword($item['UF_USER_PASSWORD']);
            $model->setOwner($item['UF_OWNER']);
            $models[] = $model;
        }
        return $models;
    }

    public function getById(int $id): ?UserSettingsModel {
        $search = ICalendarSettingsTable::query()
            ->setSelect(['*'])
            ->where('ID', $id)
            ->fetch();
        if (empty($search)) {
            return null;
        }
        $model = new UserSettingsModel();
        $model->setBaseUrl($search['UF_BASE_URL']);
        $model->setCalendarName($search['UF_CALENDAR_NAME']);
        $model->setUserLogin($search['UF_USER_LOGIN']);
        $model->setPassword($search['UF_USER_PASSWORD']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }

    public function deleteAll() {
        $search = ICalendarSettingsTable::query()
            ->setSelect(['*'])
            ->fetch();
        foreach($search as $item) {
            ICalendarSettingsTable::delete($item['ID']);
        }

    }

    public function deleteById(int $id) {
        ICalendarSettingsTable::delete($id);
    }

    public function fetchSettingsByOwner($id): ?UserSettingsModel {
        $search = ICalendarSettingsTable::query()
            ->setSelect(['ID', 'UF_BASE_URL', 'UF_CALENDAR_NAME', 'UF_USER_LOGIN', 'UF_USER_PASSWORD', 'UF_OWNER'])
            ->where('UF_OWNER', $id)
            ->fetch();
        if (empty($search)) {
            return null;
        }
        $model = new UserSettingsModel();
        $model->setBaseUrl($search['UF_BASE_URL']);
        $model->setCalendarName($search['UF_CALENDAR_NAME']);
        $model->setUserLogin($search['UF_USER_LOGIN']);
        $model->setPassword($search['UF_USER_PASSWORD']);
        $model->setOwner($search['UF_OWNER']);
        return $model;
    }
}