<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use Illuminate\Foundation\Application;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

class Base
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * @var array columns that are present in Payouts Service but not required in API DB.
     */
    protected $columnsToUnset = [];

    /**
     * Columns' name can be different in Payouts Service DB and API DB.
     * Key => Value denotes 'Payouts Service side column name' => 'API side column'
     * @var array
     */
    protected $columnConversions = [];

    protected $attributes = [];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
    }

    protected function unsetUnwantedColumns()
    {
        foreach ($this->columnsToUnset as $key)
        {
            unset($this->attributes[$key]);
        }
    }

    protected function applyConversions()
    {
        foreach ($this->columnConversions as $psColumn => $apiColumn)
        {
            $this->attributes[$apiColumn] = $this->attributes[$psColumn];

            unset($this->attributes[$psColumn]);
        }
    }

    protected function processModifications()
    {
        $this->applyConversions();

        $this->unsetUnwantedColumns();
    }
}
