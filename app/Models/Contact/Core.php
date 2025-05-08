<?php

namespace RZP\Models\Contact;

use Throwable;
use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Traits\TrimSpace;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\Contact\BatchHelper as ContactBatchHelper;
use RZP\Services\VendorPayments\Service as VendorPaymentService;
use RZP\Models\Payout\SourceRequestIDMapping\Core as SourceRequestIDMappingCore;

/**
 * Class Core
 *
 * @package RZP\Models\Contact
 */
class Core extends Base\Core
{
    use TrimSpace;

    /**
     * @var VendorPaymentService
     */
    protected $vendorPaymentService;

    public function __construct()
    {
        parent::__construct();

        $this->vendorPaymentService = $this->app['vendor-payment'];
    }

    public function create(
        array $input,
        Merchant\Entity $merchant,
        string $batchId = null,
        bool $createDuplicate = false,
        bool $allowRZPFeesContactCreation = false): Entity
    {
        $input = $this->trimSpaces($input);

        $this->trace->info(TraceCode::CONTACT_CREATE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('create', $input);

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

        if($this->isContactCreateAndEditDisabledForSourceTypeVendor($contact) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VENDOR_CONTACT_CREATION_NOT_PERMITTED,
                null,
                [
                    'merchant_id' => $contact->merchant->getId(),
                    'input'       => $input
                ]);
        }

        // Contact of type rzp_fees can be created by all internal requests.
        // So we approve it by simply looking at "$allowRZPFeesContactCreation".

        // Here "isInInternalNonRZPFees" checks whether the type of contact is internal and
        // if it is internal type, we check whether current app is allowed to create that type of contact.

        if (($allowRZPFeesContactCreation === true) or
            ((Contact\Type::isInInternalNonRZPFees($contact->getType()) === true) and
             Contact\Type::validateInternalAppAllowedContactType($contact->getType(),
                 $this->app['basicauth']->getInternalApp()) === true))
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

        $contact = $this->saveAppSpecificInformation($contact, $input, $merchant);

        $this->PushVendorEvent($contact, Contact\Constants::CONTACT_CREATED_MESSAGE);

        // Capture source request ID for contact
        $this->captureSourceRequestId($contact);

        return $contact;
    }

    /**
     * Captures the source request ID (AWS trace ID) and associates it with the contact
     *
     * @param Entity $contact The contact to associate with the source request ID
     * @return void
     */
    protected function captureSourceRequestId(Entity $contact): void
    {
        try {
            (new SourceRequestIDMappingCore())->createSourceRequestIdMapping($contact->getId(), Constants\Entity::CONTACT);
        } catch (\Throwable $e) {
            // Just log the error, don't fail the contact creation
            $this->trace->error(
                'contact.source_request_id.capture_error',
                [
                    'contact_id' => $contact->getId(),
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * This function is a subset of the above create function. It is much lighter and has been stripped to
     * the bare minimum. We shall be using this specifically for high TPS merchants.
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param array           $traceData
     *
     * @param bool            $compositePayoutSaveOrFail
     * @param array           $metadata
     *
     * @return Entity
     */
    public function createForCompositeRequest(array $input,
                                              Merchant\Entity $merchant,
                                              array $traceData,
                                              bool $compositePayoutSaveOrFail = true,
                                              array $metadata = []): Entity
    {
        $input = $this->trimSpaces($input);

        $this->trace->info(TraceCode::CONTACT_CREATE_REQUEST_FOR_COMPOSITE_PAYOUT, [
            'input'             => $traceData,
            'save_or_fail_flag' => $compositePayoutSaveOrFail,
            'metadata'          => $metadata
        ]);

        (new Validator)->validateInput('create', $input);

        // Code to check for a duplicate contact
        // TODO: Replace with Hash, just in this place (if possible)
        $contact = $this->repo->contact->getContactWithSimilarDetails($input, $merchant);

        if ($contact !== null)
        {
            $this->trace->info(
                TraceCode::DUPLICATE_CONTACT_FOUND,
                [
                    Entity::ID          => $contact->getId(),
                    'save_or_fail_flag' => $compositePayoutSaveOrFail
                ]);

            return $contact;
        }

        // NOTE: We do not have any checks on contact `type` for this new flow. We shall need to ensure that
        // merchant only passed the default 4 types, otherwise the fetch API would degrade with the `type` query param.
        $contact = (new Entity)->build($input);

        $contact->merchant()->associate($merchant);

        if (empty($metadata) === false)
        {
            if (array_key_exists(Entity::ID, $metadata) === true)
            {
                $contact->setId($metadata[Entity::ID]);
            }

            if (array_key_exists(Entity::CREATED_AT, $metadata) === true)
            {
                $contact->setCreatedAt($metadata[Entity::CREATED_AT]);
            }
        }

        $this->setTypeIfApplicable($contact, $input);

        if ($compositePayoutSaveOrFail === true)
        {
            $this->repo->saveOrFailWithoutEsSync($contact);
        }
        else
        {
            $contact->setId(Base\UniqueIdEntity::generateUniqueId());

            $contact->setCreatedAt(Carbon::now(Timezone::IST)->getTimestamp());
        }

        $this->trace->info(TraceCode::CONTACT_CREATED_FOR_COMPOSITE_PAYOUT,
                           [
                               Constants\Entity::CONTACT => $contact->getId(),
                               'save_or_fail_flag'       => $compositePayoutSaveOrFail
                           ]);


        $this->captureSourceRequestId($contact);

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

        $input = $this->trimSpaces($input);

        if($this->isContactCreateAndEditDisabledForSourceTypeVendor($contact) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VENDOR_CONTACT_UPDATION_NOT_PERMITTED,
                null,
                [
                    'merchant_id' => $contact->merchant->getId(),
                    'input'       => $input
                ]);
        }

        $this->setTypeIfApplicable($contact, $input);

        // Edit has been shifted below setTypeIfApplicable to handle the case where a merchant tries to update
        // a rzp_fees contact's type to some other type.
        $contact->edit($input);

        // to fix "" empty string in contact type
        if (empty($contact->getType()) === true)
        {
            $contact->setType(null);
        }

        // to fix "" empty string in contact email
        if (empty($contact->getEmail()) === true)
        {
            $contact->setEmail(null);
        }

        $this->repo->saveOrFail($contact);

        $this->updateAppSpecificInformation($contact, $input);

        $this->PushVendorEvent($contact, Contact\Constants::CONTACT_UPDATED_MESSAGE);

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

        // to fix "" empty string in contact type
        if (empty($contact->getType()) === true)
        {
            $contact->setType(null);
        }

        if (($type === null) or
            (empty($type) === true))
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
        $contact = $this->repo->contact->findByPublicIdAndMerchant($id, $merchant, $input);

        return $this->getAppSpecificInformation($contact);
    }

    public function fetchMultiple($merchant, $input = [])
    {
        $startTimeMs = round(microtime(true) * 1000);

        $contact = $this->repo->contact->fetch($input, $merchant->getId());

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        if($totalFetchTime > 500)
        {

            $this->trace->info(TraceCode::CONTACT_TO_REPO_FETCHTIME, [
                'duration_ms' => $totalFetchTime,
            ]);
        }

        return $this->getBulkAppSpecificInformation($contact);
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
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

    public function getAppSpecificInformation(Entity $contact): Entity
    {
        return $this->getVendorDetails($contact);
    }

    public function getBulkAppSpecificInformation(Base\PublicCollection $contacts): Base\PublicCollection
    {
        return $this->getBulkVendorDetails($contacts);
    }

    public function saveAppSpecificInformation(Entity $contact, array $input, Merchant\Entity $merchant): Entity
    {
        return $this->saveVendorDetails($contact, $input, $merchant);
    }

    function updateAppSpecificInformation(Entity $contact, array $input): Entity
    {
        return $this->updateVendorDetails($contact, $input);
    }

    /**
     * Remove leading and trailing space from type
     *
     * @param PaginationEntity $paginationEntity
     */
    public function trimContactType(PaginationEntity $paginationEntity)
    {
        $this->trace->info(
            TraceCode::START_CONTACT_TYPE_TRIMMING,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );

        $contacts = $this->repo->contact->fetchContactsHavingSpaceInType(
            $paginationEntity->getFinalMerchantList(),
            $paginationEntity->getCurrentStartTime(),
            $paginationEntity->getCurrentEndTime(),
            $paginationEntity->getLimit()
        );

        $typeFixedForMerchantIds = [];

        $contactIds = $contacts->getIds();

        while (count($contacts) > 0)
        {
            foreach ($contacts as $contact)
            {
                try
                {
                    $contactType = $contact->getType();

                    $trimmedContactType = trim(str_replace('\n', ' ', $contactType));

                    if (is_null($contactType) === false)
                    {
                        $contact->setType($trimmedContactType);

                        $merchant = $contact->merchant;

                        $merchantId = $merchant->getId();

                        if (in_array($merchantId, $typeFixedForMerchantIds, true) === false)
                        {
                            $typeObj = new Type();

                            $allCustomKeys = $typeObj->getCustom($merchant);

                            foreach ($allCustomKeys as $type)
                            {
                                if (strlen($type) !== strlen(trim($type)))
                                {
                                    $typeObj->trimType($type, $merchant);

                                    $this->trace->info(
                                        TraceCode::CONTACT_TYPE_TRIMMED_FROM_SETTING,
                                        [
                                            'type' => $type,
                                            'merchant_id' => $merchant->getId()
                                        ]
                                    );
                                }
                            }

                            array_push($typeFixedForMerchantIds, $merchantId);
                        }
                    }

                    $contact->saveOrFail();

                    $this->trace->info(
                        TraceCode::CONTACT_TYPE_TRIMMED,
                        [
                            'contact_id' => $contact->getId(),
                        ]
                    );
                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::ERROR,
                        TraceCode::CONTACT_TYPE_TRIM_FAILED,
                        [
                            'contact_id' => $contact->getId(),
                        ]
                    );
                }
            }

            $newContacts = $this->repo->contact->fetchContactsHavingSpaceInType(
                $paginationEntity->getFinalMerchantList(),
                $paginationEntity->getCurrentStartTime(),
                $paginationEntity->getCurrentEndTime(),
                $paginationEntity->getLimit()
            );

            $newContactIds = $newContacts->getIds();

            $nonCommonIdsFromLastContacts = array_diff($newContactIds, $contactIds);

            if ((count($newContacts) === 0) or
                (count($nonCommonIdsFromLastContacts) > 0))
            {
                $contactIds = $newContactIds;

                $contacts = $newContacts;
            }
            else
            {
                $data = [
                    'created_from'  => $paginationEntity->getCurrentStartTime(),
                    'created_till'  => $paginationEntity->getCurrentEndTime()
                ];

                $this->trace->info(
                    TraceCode::CONTACT_TYPE_TRIM_FOR_MERCHANTS_FAILED,
                    $data
                );

                return;
            }
        }

        $this->trace->info(
            TraceCode::CONTACT_TYPE_TRIMMED_FOR_MERCHANTS,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );
    }

    /**
     * Remove leading and trailing space from name
     *
     * @param PaginationEntity $paginationEntity
     */
    public function trimContactName(PaginationEntity $paginationEntity)
    {
        $this->trace->info(
            TraceCode::START_CONTACT_NAME_TRIMMING,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );

        $contacts = $this->repo->contact->fetchContactsHavingSpaceInName(
            $paginationEntity->getFinalMerchantList(),
            $paginationEntity->getCurrentStartTime(),
            $paginationEntity->getCurrentEndTime(),
            $paginationEntity->getLimit()
        );

        $contactIds = $contacts->getIds();

        while (count($contacts) > 0)
        {
            foreach ($contacts as $contact)
            {
                try
                {
                    $contactName = $contact->getName();

                    $trimmedContactName = trim(str_replace('\n', ' ', $contactName));

                    if (is_null($contactName) === false)
                    {
                        $contact->setName($trimmedContactName);
                    }

                    $contact->saveOrFail();

                    $this->trace->info(
                        TraceCode::CONTACT_NAME_TRIMMED,
                        [
                            'contact_id' => $contact->getId(),
                        ]
                    );
                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::ERROR,
                        TraceCode::CONTACT_NAME_TRIM_FAILED,
                        [
                            'contact_id' => $contact->getId(),
                        ]
                    );
                }
            }

            $newContacts = $this->repo->contact->fetchContactsHavingSpaceInName(
                $paginationEntity->getFinalMerchantList(),
                $paginationEntity->getCurrentStartTime(),
                $paginationEntity->getCurrentEndTime(),
                $paginationEntity->getLimit()
            );

            $newContactIds = $newContacts->getIds();

            $nonCommonIdsFromLastContacts = array_diff($newContactIds, $contactIds);

            if ((count($newContacts) === 0) or
                (count($nonCommonIdsFromLastContacts) > 0))
            {
                $contactIds = $newContactIds;

                $contacts = $newContacts;
            }
            else
            {
                $data = [
                    'created_from'  => $paginationEntity->getCurrentStartTime(),
                    'created_till'  => $paginationEntity->getCurrentEndTime()
                ];

                $this->trace->info(
                    TraceCode::CONTACT_NAME_TRIM_FOR_MERCHANTS_FAILED,
                    $data
                );

                return;
            }
        }

        $this->trace->info(
            TraceCode::CONTACT_NAME_TRIMMED_FOR_MERCHANTS,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );
    }

    private function getVendorDetails(Entity $contact): Entity
    {
        if ($contact->getType() == Type::VENDOR)
        {
            try {
                $vendor = $this->vendorPaymentService->getVendorByContactId(
                    $this->merchant,
                    ['contact_id' => $contact->getPublicId()]
                );
            } catch (BadRequestException $exception) {
                $this->trace->traceException($exception);

                return $contact;
            }

            $contact->setPaymentTerms($vendor[Entity::PAYMENT_TERMS]);
            $contact->setTdsCategory($vendor[Entity::TDS_CATEGORY]);
            $contact->setVendor($vendor);

            if (isset($vendor[Entity::EXPENSE_ID]))
            {
                $contact->setExpenseId($vendor[Entity::EXPENSE_ID]);
            }

            if (isset($vendor[Entity::GST_IN]))
            {
                $contact->setGstIn($vendor[Entity::GST_IN]);
            }
        }

        return $contact;
    }

    private function saveVendorDetails(Entity $contact, array $input, Merchant\Entity $merchant): Entity
    {
        // if request app is vendor experience then return, as the source it-self is VE - this is to avoid looping
        if ($contact->getType() != Type::VENDOR OR $this->app['basicauth']->isVendorExperienceApp() === true)
        {
            return $contact;
        }

        $createParams = [];
        if (isset($input[Entity::PAYMENT_TERMS]))
        {
            $createParams[Entity::PAYMENT_TERMS] = $input[Entity::PAYMENT_TERMS];
        }

        if (isset($input[Entity::TDS_CATEGORY]))
        {
            $createParams[Entity::TDS_CATEGORY] = $input[Entity::TDS_CATEGORY];
        }

        if (isset($input[Entity::GST_IN]))
        {
            $createParams[Entity::GST_IN] = $input[Entity::GST_IN];
        }

        if (isset($input[Entity::PAN]))
        {
            $createParams[Entity::PAN] = $input[Entity::PAN];
        }

        if (isset($input[Entity::POC_EMAILS]))
        {
            $createParams[Entity::POC_EMAILS] = $input[Entity::POC_EMAILS];
        }

        // if vendor experience is enabled then don't return
        if (empty($createParams) AND $this->isVendorExperienceEnabled($contact) === false)
        {
            return $contact;
        }

        $createParams['contact_id'] = $contact->getPublicId();
        $createReq = array_merge($createParams, $contact->getAttributes());

        try {
            $vendor = $this->vendorPaymentService->createVendor(
                $merchant,
                $createReq
            );
        } catch (BadRequestException $exception)
        {
            $this->trace->traceException($exception);

            return $contact;
        }

        $contact->setPaymentTerms($vendor[Entity::PAYMENT_TERMS]);
        $contact->setTdsCategory($vendor[Entity::TDS_CATEGORY]);
        $contact->setVendor($vendor);


        return $contact;
    }

    private function updateVendorDetails(Entity $contact, array $input): Entity
    {
        // if request app is vendor experience then return, as the source it-self is VE - this is to avoid looping
        if ($contact->getType() != Type::VENDOR OR $this->app['basicauth']->isVendorExperienceApp() === true)
        {
            return $contact;
        }

        $updateParams = [];
        if (isset($input[Entity::PAYMENT_TERMS]))
        {
            $updateParams[Entity::PAYMENT_TERMS] = $input[Entity::PAYMENT_TERMS];
        }

        if (isset($input[Entity::TDS_CATEGORY]))
        {
            $updateParams[Entity::TDS_CATEGORY] = $input[Entity::TDS_CATEGORY];
        }

        if (isset($input[Entity::GST_IN]))
        {
            $updateParams[Entity::GST_IN] = $input[Entity::GST_IN];
        }

        if (isset($input[Entity::PAN]))
        {
            $updateParams[Entity::PAN] = $input[Entity::PAN];
        }

        if (isset($input[Entity::POC_EMAILS]))
        {
            $updateParams[Entity::POC_EMAILS] = $input[Entity::POC_EMAILS];
        }

        // if vendor experience is enabled then don't return
        if (empty($updateParams) AND $this->isVendorExperienceEnabled($contact) === false)
        {
            return $this->getAppSpecificInformation($contact);
        }

        $updateParams['contact_id'] = $contact->getPublicId();
        $updateReq = array_merge($updateParams, $contact->getAttributes());

        try {
            $vendor = $this->vendorPaymentService->updateVendor($this->merchant, $updateReq);
        } catch (BadRequestException $exception)
        {
            $this->trace->traceException($exception);

            return $contact;
        }

        $contact->setPaymentTerms($vendor[Entity::PAYMENT_TERMS]);
        $contact->setTdsCategory($vendor[Entity::TDS_CATEGORY]);
        $contact->setVendor($vendor);

        return $contact;
    }

    private function getBulkVendorDetails(Base\PublicCollection $contacts): Base\PublicCollection
    {
        $contactIds = [];
        /**
         * @var Entity[] $contacts
         */
        $startTimeMs = round(microtime(true) * 1000);

        foreach ($contacts as $contact)
        {
            if ($contact->getType() == Type::VENDOR)
            {
                $contactIds[] = $contact->getPublicId();
            }
        }

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        if($totalFetchTime > 500)
        {

            $this->trace->info(TraceCode::VENDOR_DETAILS_FETCH_DURATION, [
                'duration_ms' => $totalFetchTime,
                'merchant_id' => $this->merchant->getId(),
            ]);
        }

        if (empty($contactIds))
        {
            return $contacts;
        }

        try {
            $startTimeMs = round(microtime(true) * 1000);

            $vendors = $this->vendorPaymentService->getVendorBulk(
                $this->merchant,
                ['contact_ids' => $contactIds]
            )['items'];

            $endTimeMs = round(microtime(true) * 1000);

            $totalFetchTime = $endTimeMs - $startTimeMs;

            $this->trace->info(TraceCode::VENDOR_SERVICE_FETCH_DURATION, [
                'duration_ms'    => $totalFetchTime,
                'merchant_id'    => $this->merchant->getId(),
            ]);
        } catch (BadRequestException $exception) {
            $this->trace->traceException($exception);

            return $contacts;
        }

        $contactIdVendorMap = [];
        foreach ($vendors as $vendor) {
            $contactIdVendorMap[$vendor['contact_id']] = $vendor;
        }

        foreach ($contacts as $contact) {
            if (array_key_exists($contact->getPublicId(), $contactIdVendorMap))
            {
                $vendor = $contactIdVendorMap[$contact->getPublicId()];
                $contact->setPaymentTerms($vendor[Entity::PAYMENT_TERMS]);
                $contact->setTdsCategory($vendor[Entity::TDS_CATEGORY]);
                $contact->setVendor($vendor);

                if (isset($vendor[Entity::EXPENSE_ID]))
                {
                    $contact->setExpenseId($vendor[Entity::EXPENSE_ID]);
                }

                if (isset($vendor[Entity::GST_IN]))
                {
                    $contact->setGstIn($vendor[Entity::GST_IN]);
                }
            }
        }

        return $contacts;
    }

    /**
     * Function Pushes the event to Kafka
     * - Contact Create
     * - Contact Update - for only this case change set is required by Vendor Payments service
     * - Fund Account Create
     * - Fund Account Update
     */
    public function PushVendorEvent(Entity $contact, string $eventType, FundAccountEntity $fundAccount = null): void
    {
        $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;

        // Push Vendor Event only when
        // 1. Contact is of type Vendor
        // 2. Vendor Experience is not enabled
        // 3. Request Actor is not Vendor Experience Service itself
        if (($mode !== Mode::LIVE) or
            (empty($contact->getType()) === true) or
            ($contact->getType() !== Contact\Type::VENDOR))
        {
            return;
        }

        $fundAccountID = "";
        if ($fundAccount !== null)
        {
            $fundAccountID = $fundAccount->getPublicId();
        }

        $data = array(
            Entity::ID              => $contact->getPublicId(),
            Entity::FUND_ACCOUNT_ID => $fundAccountID,
            Entity::MERCHANT_ID     => $contact->getMerchantId(),
        );

        if ($eventType === Contact\Constants::CONTACT_UPDATED_MESSAGE and !empty($contact->getChanges()))
        {
            $data[Contact\Constants::CHANGE_SET] = $contact->getChanges();
        }

        $message = [
            'event_type' => $eventType,
            'data'       => $data,
        ];

        try
        {
            app('kafkaProducerClient')->produce(Contact\Constants::CONTACT_ENTITY_UPDATE_TOPIC . '-' . $mode, stringify($message));

            $this->trace->info(TraceCode::CONTACT_UPDATED_KAFKA_MESSAGE_PUBLISHED);
        }
        catch (Throwable $exception)
        {
            $this->trace->count(Metric::CONTACT_UPDATED_TRIGGER_FAILURE);

            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::CONTACT_UPDATED_MESSAGE_FAILED);
        }
    }

    public function isContactCreateAndEditDisabledForSourceTypeVendor(Contact\Entity $contact): bool
    {
        if ($contact->getType() !== Type::VENDOR)
        {
            return false;
        }

        // Block contact update for vendor type if merchant has `vendor_onboarding_enabled` feature enabled
        // This is to ensure that the vendor contact updation is done via vendor experience service only
        if ($contact->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::VENDOR_ONBOARDING_ENABLED) === true
                AND $this->app['basicauth']->isVendorExperienceApp() === false)
        {
            return true;
        }

        return false;
    }

    public function isVendorExperienceEnabled(Contact\Entity $contact): bool
    {
        if ($contact->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::VENDOR_EXPERIENCE_ENABLED) === true)
        {
            return true;
        }

        return false;
    }

    public function isSplitzExperimentEnabled(array $properties, string $checkVariant, string $traceCode = null): bool
    {
        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'splitz_output' => $response,
            ]);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === $checkVariant)
            {
                return true;
            }
        }
        catch (\Throwable $e)
        {
            $id = $properties['id'] ?? null;

            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;

            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);
        }

        return false;
    }

    public function getHashOfRequestBody(Array $input): string
    {
        try {
            ksort($input);

            $encodedRequestBody = json_encode($input);

            return hash('sha256', $encodedRequestBody);
        } catch (\Throwable $e) {
            //Fallback to contact name if there is an error in hashing process
            return (isset($input[Entity::NAME]) === true) ? strtolower(implode('_', explode(' ', $input[Entity::NAME]))) : '';
        }
    }
}
