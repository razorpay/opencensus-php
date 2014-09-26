<?php

namespace Dashboard;

class Logs extends \Models\Base\Entity
{
    protected $table = 'dashboard_logs';

    const ID = 'id';

    const JSON = 'json';

    protected $guarded = array();
}