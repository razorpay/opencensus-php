<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Admin\Admin as AdminModel;

class AdminEntityInputFilter
{
    public static function getAdminInput(array $entry, AdminModel\Entity $admin): array
    {
        $validInputArray = ['name',
                            'roles',
                            'groups',
                            'locked',
                            'disabled',
                            'allow_all_merchants'];

        foreach ($entry as $key => $value)
        {
            if (in_array($key, $validInputArray))
            {
                $input = [$key => $entry[$key] ?? $admin[$key]];
            }
            else
            {
                $input = [];

            }
        }

        return $input;
    }
}
