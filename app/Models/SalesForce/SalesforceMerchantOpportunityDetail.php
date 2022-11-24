<?php


namespace RZP\Models\SalesForce;


use Carbon\Carbon;

class SalesforceMerchantOpportunityDetail implements \JsonSerializable {
    /** @var $merchantId string */
    private $merchantId;
    /** @var $opportunityName string */
    private $opportunityName;
    /** @var $opportunityStage string */
    private $opportunityStage;

    private $opportunityLossReason;

    /** @var $opportunityOwnerName string */
    private $opportunityOwnerName;

    private $opportunityOwnerRole;
    /** @var $opportunityLastModifiedTime Carbon */
    private $opportunityLastModifiedTime;

    /**
     * @return string
     */
    public function getMerchantId(): string {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     */
    public function setMerchantId(string $merchantId): void {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string
     */
    public function getOpportunityName(): string {
        return $this->opportunityName;
    }

    /**
     * @param string $opportunityName
     */
    public function setOpportunityName(string $opportunityName): void {
        $this->opportunityName = $opportunityName;
    }

    /**
     * @return string
     */
    public function getOpportunityStage(): string {
        return $this->opportunityStage;
    }

    /**
     * @param string $opportunityStage
     */
    public function setOpportunityStage(string $opportunityStage): void {
        $this->opportunityStage = $opportunityStage;
    }


    public function getOpportunityLossReason() {
        return $this->opportunityLossReason;
    }


    public function setOpportunityLossReason($opportunityLossReason) {
        $this->opportunityLossReason = $opportunityLossReason;
    }

    /**
     * @return string
     */
    public function getOpportunityOwnerName(): string {
        return $this->opportunityOwnerName;
    }

    /**
     * @param string $opportunityOwnerName
     */
    public function setOpportunityOwnerName(string $opportunityOwnerName): void {
        $this->opportunityOwnerName = $opportunityOwnerName;
    }


    public function getOpportunityOwnerRole() {
        return $this->opportunityOwnerRole;
    }


    public function setOpportunityOwnerRole($opportunityOwnerRole): void {
        $this->opportunityOwnerRole = $opportunityOwnerRole;
    }

    /**
     * @return int
     */
    public function getOpportunityLastModifiedTime() {
        return $this->opportunityLastModifiedTime->timestamp;
    }

    /**
     * @param string $opportunityLastModifiedTime
     */
    public function setOpportunityLastModifiedTime(string $opportunityLastModifiedTime): void {
        $this->opportunityLastModifiedTime = Carbon::parse($opportunityLastModifiedTime);
    }


    public function jsonSerialize() : array
    {
        return [
            'merchantId'                  => $this->getMerchantId(),
            'opportunityName'             => $this->getOpportunityName(),
            'opportunityStage'            => $this->getOpportunityStage(),
            'opportunityLossReason'       => $this->getOpportunityLossReason(),
            'opportunityOwnerName'        => $this->getOpportunityOwnerName(),
            'opportunityOwnerRole'        => $this->getOpportunityOwnerRole(),
            'opportunityLastModifiedTime' => $this->getOpportunityLastModifiedTime()
        ];
    }
}
