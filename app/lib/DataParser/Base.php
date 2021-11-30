<?php

namespace RZP\lib\DataParser;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;

class Base
{
    protected $input;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    //data parser types
    const TYPEFORM = 'Typeform';

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Base constructor.
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->input = $input;
    }
}
