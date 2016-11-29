<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Admin\Permission\Entity as PermissionEntity;

class Permission extends Base
{
    public function createDefaultPermissions()
    {
        $records = self::getPermissionRecordsFromFile(storage_path().'/permissions/permissions.csv');

        $columns = [
            PermissionEntity::ID,
            PermissionEntity::NAME,
            PermissionEntity::CATEGORY,
            PermissionEntity::DESCRIPTION,
            PermissionEntity::CREATED_AT,
            PermissionEntity::UPDATED_AT,
        ];

        $assocRecords = array();

        foreach ($records as $index => $attributes)
        {
            $attributes = array_combine($columns, $attributes);

            $assocRecords[] = $this->fixtures->create('permission', $attributes);
        }

        return new PublicCollection($assocRecords);
    }

    /**
     * Returns the records read from a file as an array
     *
     * @return array $records permission records
     */
    private static function getPermissionRecordsFromFile($path)
    {
        if (is_readable($path) === false)
        {
            throw new RuntimeException($path . ' file is either not found or not readable');
        }

        $fileHandle = fopen($path, 'r');

        $records = array();

        $time = time();

        while(($permissionRecord = fgetcsv($fileHandle)) !== false)
        {
            $permissionRecord[PermissionEntity::CREATED_AT] = $time;
            $permissionRecord[PermissionEntity::UPDATED_AT] = $time;
            array_push($records, $permissionRecord);
        }

        fclose($fileHandle);

        return $records;
    }
}
