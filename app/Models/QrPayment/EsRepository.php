<?php

namespace RZP\Models\QrPayment;

use RZP\Constants\Es;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Payment\Entity as PaymentEntity;

class EsRepository extends Base\EsRepository
{
    const CUSTOMER_FIELDS_PREFIX = 'cust_';

    const CUSTOMER_EMAIL   = self::CUSTOMER_FIELDS_PREFIX . Customer\Entity::EMAIL;

    const NOTES_NEW = 'notes_new';

    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::QR_CODE_ID,
        Entity::CREATED_AT,
        Entity::PAYMENT_ID,
        Entity::PROVIDER_REFERENCE_ID,
        PaymentEntity::NOTES,
        PaymentEntity::STATUS,
        self::CUSTOMER_EMAIL,
        self::NOTES_NEW,
    ];

    public function buildQueryForEntityType(array &$query, $value)
    {
        return $query;
    }

    public function buildQueryForProviderReferenceId(array &$query, $value)
    {
        $this->addTermFilter($query, Entity::PROVIDER_REFERENCE_ID, $value);
    }

    public function buildQueryForQrCodeId(array &$query, $value)
    {
        $this->addTermFilter($query, Entity::QR_CODE_ID, $value);
    }

    public function buildQueryForPaymentId(array &$query, $value)
    {
        $this->addTermFilter($query, Entity::PAYMENT_ID, $value);
    }

    public function buildQueryForCustEmail(array &$query, $value)
    {
        $this->addMatchPhrasePrefix($query, self::CUSTOMER_EMAIL, $value);
    }

    public function buildQueryForNotes(array &$query, string $value)
    {
        $clause = [
            Es::MATCH => [
                'notes_new.value' => [
                    Es::QUERY => $value,
                ],
            ],
        ];

        $this->addMust($query, $clause);
    }
}
