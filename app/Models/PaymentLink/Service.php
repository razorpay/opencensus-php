<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->payment_link;
    }

    public function sendNotification(string $id, array $input)
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $this->core->sendNotification($paymentLink, $input);
    }

    public function expireLinks(): array
    {
        return $this->core->expireLinks();
    }
}
