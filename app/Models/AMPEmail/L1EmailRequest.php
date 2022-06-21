<?php
namespace RZP\Models\AMPEmail;

class L1EmailRequest extends EmailRequest
{
    public $email;

    public $campaignId;

    public $token;

    public $merchant;

    public $merchantDetail;


    public function __construct(Entity $ampEmail, \RZP\Models\Merchant\Entity $merchant)
    {
        parent::__construct(config(Constants::APPLICATIONS_MAILMODO)[Constants::L1_CAMPAIGN_ID], $merchant->getEmail(), $ampEmail->getId());

        $this->merchant = $merchant;

        $this->merchantDetail = $merchant->merchantDetail;
    }

    public function getPayload(): array
    {

        return [
            "email" => $this->email,
            "data"  => [
                "token" => $this->token
            ]
        ];
    }

}
