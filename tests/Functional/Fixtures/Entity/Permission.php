<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use Config;
use DB;

use RZP\Constants\Table;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Admin\Permission\Repository as PermRepo;
use RZP\Models\Admin\Permission\Entity as PermissionEntity;

class Permission extends Base
{
    public function createDefaultPermissions()
    {
        $permissionCategories = Config::get('heimdall.permissions');

        $records = [];

        foreach ($permissionCategories as $permissionCategory => $permissions)
        {
            foreach ($permissions as $permission => $permissionValue)
            {
                $desc = isset($permissionValue['description']) ? $permissionValue['description'] : '';

                $assignable = $permissionValue['assignable'] ?? false;

                $row = [
                    PermissionEntity::NAME        => $permission,
                    PermissionEntity::CATEGORY    => $permissionCategory,
                    PermissionEntity::DESCRIPTION => $desc,
                    PermissionEntity::CREATED_AT  => time(),
                    PermissionEntity::UPDATED_AT  => time(),
                    PermissionEntity::ASSIGNABLE  => $assignable,
                ];

                $records[] = $this->fixtures->create('permission', $row);
            }
        }

        return new PublicCollection($records);
    }

    public function createDefaultPermissionsLive()
    {
        $permissionCategories = Config::get('heimdall.permissions');

        $records = [];

        foreach ($permissionCategories as $permissionCategory => $permissions)
        {
            foreach ($permissions as $permission => $permissionValue)
            {
                $desc = isset($permissionValue['description']) ? $permissionValue['description'] : '';

                $assignable = $permissionValue['assignable'] ?? false;

                $row = [
                    PermissionEntity::NAME        => $permission,
                    PermissionEntity::CATEGORY    => $permissionCategory,
                    PermissionEntity::DESCRIPTION => $desc,
                    PermissionEntity::CREATED_AT  => time(),
                    PermissionEntity::UPDATED_AT  => time(),
                    PermissionEntity::ASSIGNABLE  => $assignable,
                ];

                $records[] = $this->fixtures->on('live')->create('permission', $row);
            }
        }

        return new PublicCollection($records);
    }

    public function getAllPermissions()
    {
        $permissions = DB::table(Table::PERMISSION)
                            ->pluck('id');

        return $permissions;
    }
}
