<?php

namespace RZP\Dashboard;

class Logs extends \RZP\Models\Base\Entity
{
    protected $table = 'dashboard_logs';

    const ID = 'id';

    const JSON = 'json';

    protected $guarded = array();
}
