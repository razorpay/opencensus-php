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

    /**
     * {@inheritDoc}
     * Overridden as it expects in arguments & passes around $input to repository method
     */
    public function fetch(string $id, array $input): array
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    public function create(array $input): array
    {
        $entity = $this->core->create($input, $this->merchant, $this->user);

        return $entity->toArrayPublic();
    }

    public function sendNotification(string $id, array $input)
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $this->core->sendNotification($paymentLink, $input);
    }

    public function expirePaymentLinks(): array
    {
        return $this->core->expirePaymentLinks();
    }

    public function deactivate(string $id): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $paymentLink = $this->core->deactivate($paymentLink);

        return $paymentLink->toArrayPublic();
    }

    public function activate(string $id, array $input): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $paymentLink = $this->core->activate($paymentLink, $input);

        return $paymentLink->toArrayPublic();
    }

    public function getViewNameAndPayload(string $id)
    {
        /** @var Entity $paymentLink */
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $viewPayload = $this->core->getHostedViewPayload($paymentLink);

        $view = $this->core->getHostedViewTemplate($paymentLink);

        return [$view, $viewPayload];
    }
}
