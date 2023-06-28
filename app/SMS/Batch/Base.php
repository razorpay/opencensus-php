<?php

namespace RZP\SMS\Batch;

class Base
{

    protected $templateNamespace;

    protected $sender;

    protected $template;

    protected $batch;

    protected $merchant;

    protected $contentParams = null;

    public function __construct(
        array $batch,
        $merchant,
    ) {
        $this->batch = $batch;
        $this->merchant = $merchant;
    }

    public function getSMSPayload() {
        return [
            'ownerId'           => $this->merchant->getId(),
            'ownerType'         => 'merchant',
            'orgId'             => $this->merchant->getOrgId(),
            'sender'            => $this->sender,
            'destination'       => $this->merchant->merchantDetail->getContactMobile(),
            'templateName'      => $this->template,
            'templateNamespace' => $this->templateNamespace,
            'language'          => 'english',
            'contentParams'     => $this->contentParams,
        ];
    }

}
