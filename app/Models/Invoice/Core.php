<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Customer;

class Core extends Base\Core
{
    protected $itemService;

    protected $itemRepository;
    protected $customerRepository;

    protected $itemCore;
    protected $orderCore;
    protected $customerCore;

    protected $invoiceGenerator;

    public function __construct()
    {
        parent::__construct();

        $this->itemService = new LineItem\Service();

        $this->itemRepository = $this->repo->line_item;
        $this->customerRepository = $this->repo->customer;

        $this->itemCore = new LineItem\Core();
        $this->orderCore = new Order\Core();
        $this->customerCore = new Customer\Core();
    }

    // TODO: The whole create process should be in a transaction
    public function create(array $input)
    {
        // TODO: Should we move this to validator?
        $this->validateRequest($input);

        $invoice = (new Generator($this->merchant))->generate($input);

        return $invoice;
    }

    protected function validateRequest(array $input)
    {
        assert(isset($input[Entity::CUSTOMER]));

        assert((isset($input[Entity::LINE_ITEMS])) and
               (count($input[Entity::LINE_ITEMS]) > 0));
    }
}