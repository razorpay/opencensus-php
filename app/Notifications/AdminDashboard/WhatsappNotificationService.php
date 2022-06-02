<?php

namespace RZP\Notifications\AdminDashboard;

use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Notifications\BaseNotificationService;

class WhatsappNotificationService extends BaseNotificationService
{
    protected const ADMIN_DASHBOARD_PREFIX = 'admin_dashboard';

    public function send()
    {
        $this->app['stork_service']->sendWhatsappMessage(
            $this->mode,
            $this->getTemplateMessage(),
            $this->getPhone(),
            $this->getPayload()
        );
    }

    protected function getPayload(): array
    {
        $merchant = $this->getMerchant();

        $noOfDocuments = count($this->args['documents']);

        $templateName = Events::NO_OF_DOCUMENTS_NEEDS_CLARIFICATION_TEMPLATE_NAMES[$noOfDocuments];

        $ticketId = $this->args['ticket']->getId();

        $fdInstance = $this->args['ticket']->getFdInstance();

        $payload = [
            'ownerId'          => $merchant->getId(),
            'ownerType'        => Merchant\Constants::MERCHANT,
            'template'         => $this->getTemplateMessage(),
            'template_name'    => $templateName,
            'receiver'         => $this->getPhone(),
            'source'           => 'api.' . $this->mode . '.admin_dashboard',
            'params'           => [
                '1'            => $merchant->getName(),
            ],
            'is_cta_template'  => true,
            'button_url_param' => sprintf(Merchant\FreshdeskTicket\Constants::SUPPORT_TICKET_DASHBOARD_BUTTON_URL, $fdInstance, $ticketId)
        ];

        $count = 2;

        foreach ($this->args['documents'] as $document)
        {
            $payload['params'][strval($count)] = $document;

            $count++;
        }

        return $payload;
    }

    private function getTemplateMessage(): string
    {
        return Events::NO_OF_DOCUMENTS_NEEDS_CLARIFICATION_TEMPLATES[count($this->args['documents'])];
    }

    private function getPhone(): string
    {
        $merchant = $this->getMerchant();

        return $merchant->merchantDetail->getContactMobile();
    }

    protected function getMerchant()
    {
        return $this->args['ticket']->merchant;
    }
}
