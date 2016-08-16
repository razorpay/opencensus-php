<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create($input)
    {
        $invoice = $this->core->create($input);

        return $invoice->toArrayPublic();
    }

    public function fetch($id)
    {
        Entity::verifyIdAndStripSign($id);

        $invoice = $this->repo->invoice->findByIdAndMerchantId($id, $this->merchant->getId());

        return $invoice->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $invoices = $this->repo->invoice->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function sendNotificationsInBulk()
    {
        return (new Notifier())->sendNotificationsInBulk();
    }
}