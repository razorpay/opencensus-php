<?php


namespace RZP\Models\Order;

use App;

class Hook
{
    /**
     * @var array
     */
    protected $hooks = [];

    /**
     * Repository manager instance
     */
    protected $repo;
    /**
     * @var array
     */
    protected $orderInput;

    public function __construct(array $input)
    {
        $this->orderInput = $input;

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    /**
     * Process all the hook methods
     */
    public function process()
    {
        foreach ( $this->hooks as $param => $method) {

            if (array_key_exists($param, $this->orderInput) === true)
            {
                $paramInput = $this->orderInput[$param];

                $this->$method($paramInput);
            }
        }
    }

}
