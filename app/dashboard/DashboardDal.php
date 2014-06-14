<?php

namespace Dashboard;

class DashboardDal extends \Models\DAL\DAL
{
    protected $table = 'dashboard_logs';

    public static function persistAfterFail($json)
    {
        $attributes = array(
            'json'  =>  $json
        );

        return static::createOrFail($attributes);
    }
}