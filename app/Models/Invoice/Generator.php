<?php

namespace RZP\Models\Invoice;

use RZP\Models\Merchant;
use App;
use RZP\Models\Order;
use RZP\Models\Customer;

class Generator
{
    protected $app;
    protected $trace;
    protected $mode;
    protected $merchant;
    protected $customer;
    protected $order;
    protected $repo;
    protected $orderRepo;

    public function __construct(Merchant\Entity $merchant, Order\Entity $order, Customer\Entity $customer)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $merchant;

        $this->order = $order;

        $this->customer = $customer;

        $this->repo = $this->app['repo'];
    }

    public function generate(array $input, array $items)
    {
        $invoice = new Entity();

        unset($input[Entity::CUSTOMER_DETAILS]);
        unset($input[Entity::ITEMS]);

        $invoice->build($input);

        $invoice->order()->associate($this->order);

        $invoice->customer()->associate($this->customer);

        $this->repo->saveOrFail($invoice);

        return $invoice;
    }
}