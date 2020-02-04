<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use App;

use RZP\Models\Merchant\AutoKyc\Processor;

abstract class BaseProcessor implements Processor
{
    use MozartServiceClient;

    protected $config;

    protected $trace;

    protected $app;

    protected $input;

    /**
     * @var int Default timeout
     */
    protected $timeout = 10; //seconds

    public function __construct(array $input)
    {
        $app = App::getFacadeRoot();

        $this->app    = $app;
        $this->config = $app['config']['applications.mozart'];
        $this->trace  = $app['trace'];

        $this->input  = $input;
    }
}
