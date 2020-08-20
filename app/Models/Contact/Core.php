<?php

namespace RZP\Models\Contact;

use RZP\Constants;
use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Traits\TrimSpace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Contact\BatchHelper as ContactBatchHelper;

/**
 * Class Core
 *
 * @package RZP\Models\Contact
 */
class Core extends Base\Core
{
    use TrimSpace;

    public function create(
        array $input,
        Merchant\Entity $merchant,
        string $batchId = null,
        bool $createDuplicate = false,
        bool $allowRZPFeesContactCreation = false): Entity
    {
        $this->trace->info(TraceCode::CONTACT_CREATE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('create', $input);

        $merchantId = $merchant->getId();

        $treatment = $this->app->razorx->getTreatment(
            $merchantId,
            RazorxTreatment::TRIM_SPACE_FOR_MERCHANT,
            $this->mode,
            Entity::CONTACT_RX_RETRY_COUNT
        );

        if ($treatment === 'on')
        {
            $input = $this->trimSpaces($input);
        }

        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $result = $this->repo->contact->fetchByIdempotentKey($input[Entity::IDEMPOTENCY_KEY],
                                                                 $merchant->getId(),
                                                                 $batchId);

            if ($result !== null)
            {
                $this->trace->info(TraceCode::CONTACT_ALREADY_EXISTS_WITH_SAME_IDEMPOTENCY_KEY,
                                   [
                                       'input' => $result->toArrayPublic(),
                                       Entity::IDEMPOTENCY_KEY => $input[Entity::IDEMPOTENCY_KEY]
                                   ]);

                return $result;
            }
        }

        if ($createDuplicate === false)
        {
            $contact = $this->repo->contact->getContactWithSimilarDetails($input, $merchant);

            if (($treatment === 'on') and
                ($contact === null))
            {
                $treatmentTrimMigrationCompleted = $this->app->razorx->getTreatment(
                    $merchantId,
                    RazorxTreatment::TRIM_MIGRATION_COMPLETED,
                    $this->mode,
                    Entity::CONTACT_RX_RETRY_COUNT
                );

                if ($treatmentTrimMigrationCompleted !== 'on')
                {
                    $contact = $this->repo->contact->getContactWithTrimmedSimilarDetails($input, $merchant);
                }
            }

            if ($contact !== null)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_CONTACT_FOUND,
                    [
                        Entity::ID         => $contact->getId(),
                        Entity::BATCH_ID   => $batchId,
                    ]);

                return $contact;
            }
        }

        $contact = (new Entity)->build($input);

        $contact->merchant()->associate($merchant);

        if (($allowRZPFeesContactCreation === true) or
            ($this->isTaxPaymentContactRequest($contact) === true))
        {
            (new Type)->setTypeForInternalContact($contact, $input[Entity::TYPE]);
        }
        else
        {
            $this->setTypeIfApplicable($contact, $input);
        }

        if (empty($batchId) === false)
        {
            $contact->setBatchId($batchId);
        }

        $this->repo->saveOrFail($contact);

        $this->trace->info(TraceCode::CONTACT_CREATED,
            [
                Constants\Entity::CONTACT => $contact->getId(),
            ]);

        return $contact;
    }

    /**
     * This function will check that this is trying to create the TaxPayment internal contact
     * Also checks if the request source is valid
     *
     * @param Entity $contact
     * @return bool
     */
    protected function isTaxPaymentContactRequest(Entity $contact): bool
    {
        if (($contact->getType() === Type::TAX_PAYMENT_INTERNAL_CONTACT) and
            ($this->app['basicauth']->isVendorPaymentApp() === true))
        {
            return true;
        }

        return false;
    }

    public function update(Entity $contact, array $input): Entity
    {
        $this->trace->info(
            TraceCode::CONTACT_UPDATE_REQUEST,
            [
                'id'    => $contact->getId(),
                'input' => $input,
            ]);

        (new Validator)->validateInput('edit', $input);

        $this->setTypeIfApplicable($contact, $input);

        $input = $this->trimSpacesIfMerchantEnabled($input, $this->merchant->getId());

        // Edit has been shifted below setTypeIfApplicable to handle the case where a merchant tries to update
        // a rzp_fees contact's type to some other type.
        $contact->edit($input);

        // Because edit has been shifted below setTypeIfApplicable(), below condition cannot be written inside it
        if (empty($input[Entity::TYPE]) === true)
        {
            $contact->setType(null);
        }

        $this->repo->saveOrFail($contact);

        return $contact;
    }

    public function delete(Entity $contact)
    {
        // If we ever decide to make this public. Will need to make sure that Internal contact cannot be deleted.
        $this->trace->info(TraceCode::CONTACT_DELETE_REQUEST, ['id' => $contact->getId()]);

        return $this->repo->deleteOrFail($contact);
    }

    protected function setTypeIfApplicable(Entity $contact, array $input)
    {
        // If contact type is 'rzp_fees', we won't allow the merchant to create/update the contact
        // If we need to update this, it should be done at the DB level
        if (Type::isInInternal($contact->getType()) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                null,
                [
                    'contact_id' => $contact->getId(),
                    'input'      => $input
                ]);
        }

        $type = $input[Entity::TYPE] ?? null;

        if (empty($input[Entity::TYPE]) === true)
        {
            $contact->setType(null);

            return;
        }

        if ($type === null)
        {
            return;
        }

        (new Type)->setTypeForContact($contact, $type);
    }

    public function processEntryForContact(
        array $entry,
        string $batchId,
        bool $createDuplicate)
    {
        $contact = $entry[ContactBatchHelper::CONTACT];

        $contactId = (isset($contact[ContactBatchHelper::ID]) === true) ? $contact[ContactBatchHelper::ID] : null;

        if (empty($contactId) === false)
        {
            return $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);
        }

        $input = ContactBatchHelper::getContactInput($entry);

        $contact = $this->create($input, $this->merchant, $batchId, $createDuplicate);

        return $contact;
    }

    public function fetch($id, $merchant, $input = [])
    {
        $contact =  $this->repo->contact->findByPublicIdAndMerchant($id, $merchant, $input);

        return $contact;
    }

    public function createRZPFeesContact($merchant)
    {
        $this->trace->info(TraceCode::RZP_FEES_CONTACT_CREATE_REQUEST, [
            'merchant_id' => $merchant->getId()
        ]);

        $contactData = [
            'name'  => $this->config['banking_account.razorpayx_fee_details.name'],
            'type'  => Type::RZP_FEES,
        ];

        $contact = $this->create($contactData, $merchant, null, true, true);

        return $contact;
    }
}
