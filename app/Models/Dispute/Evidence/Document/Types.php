<?php


namespace RZP\Models\Dispute\Evidence\Document;


class Types
{
    const SHIPPING_PROOF             = 'shipping_proof';
    const BILLING_PROOF              = 'billing_proof';
    const CANCELLATION_PROOF         = 'cancellation_proof';
    const CUSTOMER_COMMUNICATION     = 'customer_communication';
    const EXPLANATION_LETTER         = 'explanation_letter';
    const REFUND_CONFIRMATION        = 'refund_confirmation';
    const ACCESS_ACTIVITY_LOG        = 'access_activity_log';
    const TERMS_AND_CONDITIONS       = 'terms_and_conditions';
    const OTHERS                     = 'others';
    const REFUND_CANCELLATION_POLICY = 'refund_cancellation_policy';
    const PROOF_OF_SERVICE           = 'proof_of_service';

    const NAME             = 'name';
    const LABEL            = 'label';
    const DESCRIPTION      = 'description';

    protected static $typesMetadataMap = [

        self::SHIPPING_PROOF             => [
            self::NAME        => self::SHIPPING_PROOF,
            self::LABEL       => 'Shipping Proof',
            self::DESCRIPTION => 'Document(s) which serves as proof that the product was shipped to the customer at the customer provided address. It should show the customer’s full shipping address, if possible.',
        ],
        self::BILLING_PROOF              => [
            self::NAME        => self::BILLING_PROOF,
            self::LABEL       => 'Billing Proof',
            self::DESCRIPTION => 'Document(s) which serves as proof of order confirmation such as receipt.',
        ],
        self::CANCELLATION_PROOF         => [
            self::NAME        => self::CANCELLATION_PROOF,
            self::LABEL       => 'Cancellation Proof',
            self::DESCRIPTION => 'Document(s) that serves as a proof that this product/service was cancelled.',
        ],
        self::CUSTOMER_COMMUNICATION     => [
            self::NAME        => self::CUSTOMER_COMMUNICATION,
            self::LABEL       => 'Customer Communication',
            self::DESCRIPTION => 'Document(s) listing any written/email communication from the customer confirming that the customer received the product/service or is satisfied with the product/service.',
        ],
        self::PROOF_OF_SERVICE           => [
            self::NAME        => self::PROOF_OF_SERVICE,
            self::LABEL       => 'Proof Of Service',
            self::DESCRIPTION => 'Documentation(s) showing proof of service provided to the customer.',
        ],
        self::EXPLANATION_LETTER         => [
            self::NAME        => self::EXPLANATION_LETTER,
            self::LABEL       => 'Explanation Letter',
            self::DESCRIPTION => 'Any explanation letter(s) from you specifying information pertinent to the dispute/ payment that needs to be taken into consideration for processing the dispute.',
        ],
        self::REFUND_CONFIRMATION        => [
            self::NAME        => self::REFUND_CONFIRMATION,
            self::LABEL       => 'Refund Confirmation',
            self::DESCRIPTION => 'Documentation(s) showing proof that the refund was provided to the customer',
        ],
        self::ACCESS_ACTIVITY_LOG        => [
            self::NAME        => self::ACCESS_ACTIVITY_LOG,
            self::LABEL       => 'Access Activity Log',
            self::DESCRIPTION => 'Documentation(s) of any server or activity logs which prove that the customer accessed or downloaded the purchased digital product.',
        ],
        self::REFUND_CANCELLATION_POLICY => [
            self::NAME        => self::REFUND_CANCELLATION_POLICY,
            self::LABEL       => 'Refund Cancellation Policy',
            self::DESCRIPTION => 'Document(s) listing your refund and/or cancellation policy, as shown to the customer.',
        ],
        self::TERMS_AND_CONDITIONS       => [
            self::NAME        => self::TERMS_AND_CONDITIONS,
            self::LABEL       => 'Terms And Conditions',
            self::DESCRIPTION => 'Document(s) listing your sales terms and conditions, as shown to the customer.',
        ],
        self::OTHERS                     => [
            self::NAME        => self::OTHERS,
            self::LABEL       => 'Others',
            self::DESCRIPTION => 'Field specifying any other type of evidence documents to be uploaded as a part of contesting a dispute',
        ],
    ];

    public static function getTypesMetadata(): array
    {
        return array_values(self::$typesMetadataMap);
    }

    public static function getTypes(): array
    {
        return array_keys(self::$typesMetadataMap);
    }

    public static function isValidType(string $type) : bool
    {
        return in_array($type, self::getTypes()) === true;
    }
}
