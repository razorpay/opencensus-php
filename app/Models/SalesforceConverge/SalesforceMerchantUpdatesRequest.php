<?php
namespace RZP\Models\SalesforceConverge;

use RZP\Models\Merchant\Entity as MerchantEntity;

class SalesforceMerchantUpdatesRequest
{
    public    $merchant_id;

    public    $activated;

    public    $activated_at;

    public    $activation_progress;

    public    $activation_status;

    public    $activation_flow;

    public    $foh;

    public    $lead_score_pg;

    public    $process;

    public function __construct(MerchantEntity $merchant, string $process)
    {
        $this->merchant_id = $merchant->getId();

        $this->activated = $merchant->isActivated();

        $this->activated_at = date('Y-m-d',$merchant->getActivatedAt());

        $this->activation_progress = optional($merchant->merchantDetail)->getActivationProgress() ?? 0;

        $this->activation_status = optional($merchant->merchantDetail)->getActivationStatus();

        $this->activation_flow = optional($merchant->merchantDetail)->getActivationFlow();

        $this->foh = $merchant->isFundsOnHold();

        $this->lead_score_pg = optional($merchant->merchantBusinessDetail)->getTotalLeadScore() ?? 0;

        $this->process = $process;

    }

    public function getPath(): ?string
    {
        return "/services/data/v53.0/sobjects/CX_Merchant_Event__e";
    }

    public function getFormattedRequest(): ?array
    {
        $requestArray = (array) $this;

        return [ 'CX_Source__c'     =>  'admindashboard',
                 'CX_Process__c'    =>  $this->process,
                 'CX_Payload__c'    =>  json_encode($requestArray)];
    }
}
