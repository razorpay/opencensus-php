<?php

namespace Dashboard;

class DashboardDal extends \Models\Base\Entity
{
    protected $table = 'dashboard_logs';

    protected $guarded = array();

    public static function persistAfterFail($data)
    {
        $attributes = array(
            'json'  =>  json_encode($data)
        );

        return static::createOrFail($attributes);
    }
}