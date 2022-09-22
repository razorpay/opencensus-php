<?php

namespace RZP\Models\SalesForce;

use ReflectionClass;
use RZP\Exception\InvalidArgumentException;

class SalesForceEventRequestDTO {

    /** @var $eventType SalesForceEventRequestType */
    private $eventType;
    /** @var $eventProperties array */
    private $eventProperties;

    /**
     * @return SalesForceEventRequestType
     */
    public function getEventType(): SalesForceEventRequestType {
        return $this->eventType;
    }

    /**
     * @param SalesForceEventRequestType $eventType
     */
    public function setEventType(SalesForceEventRequestType $eventType): void {
        $this->eventType = $eventType;
    }

    /**
     * @return array
     */
    public function getEventProperties(): array {
        return $this->eventProperties;
    }

    /**
     * @param array $eventProperties
     */
    public function setEventProperties(array $eventProperties): void {
        $this->eventProperties = $eventProperties;
    }

}

class SalesForceEventRequestType {
    private const CURRENT_ACCOUNT_INTEREST = 'CURRENT_ACCOUNT_INTEREST';
    private const LOS_NEW_APPLICATION = 'LOS_NEW_APPLICATION';
    private const RX_WEBSITE_SF_EVENTS = 'RX_WEBSITE_SF_EVENTS';
    private const SHOPIFY_MIGRATION_REQUEST = 'SHOPIFY_MIGRATION_REQUEST';
    private const CURRENT_ACCOUNT_CLARITY_CONTEXT = 'CURRENT_ACCOUNT_CLARITY_CONTEXT';
    private const VENDOR_PAYMENT_EVENT = 'VENDOR_PAYMENT_EVENT';

    private $value;

    public function __construct(string $eventType) {
        $c = new ReflectionClass($this);

        if (!in_array($eventType, $c->getConstants())) {
            throw new InvalidArgumentException("Invalid Event Type");
        }
        $this->value = $eventType;
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->value;
    }
}


