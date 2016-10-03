<?php

namespace RZP\Models\FileHandler\StorageService\Base;

use App;

class Handler
{
    protected $app;

    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    protected function trace()
    {
        $trace = \Trace::getFacadeRoot();

        return $trace;
    }
}
?>
