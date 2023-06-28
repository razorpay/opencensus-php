<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Constants\HyperTrace;
use RZP\Models\Base;
use RZP\Models\Address;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\AccountV2;
use RZP\Exception\BadRequestException;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Trace\Tracer;
use RZP\Http\Controllers\MerchantOnboardingProxyController;

class Core extends Base\Core
{
    const STAKEHOLDER_CREATE_MUTEX_PREFIX = 'api_stakeholder_create_';

    public function syncMerchantDetailFieldsToStakeholder(Detail\Entity $merchantDetails, array $input)
    {
        $fields = Constants::MERCHANT_DETAILS_COMMON_EDITABLE_FIELDS;

        $stakeholderInput = [];

        foreach ($fields as $key => $val)
        {
            if (array_key_exists($val, $input) === true)
            {
                $stakeholderInput[$key] = $input[$val];
            }
        }

        if (empty($stakeholderInput) === true)
        {
            return;
        }

        $stakeholder = $this->createOrFetchStakeholder($merchantDetails);

        $this->trace->info(TraceCode::MERCHANT_SAVE_STAKEHOLDER_DETAILS, $stakeholderInput);

        $stakeholder->edit($stakeholderInput);

        $this->repo->stakeholder->saveOrFail($stakeholder);
    }

    public function create(string $merchantId, array $input): Entity
    {
        $this->trace->info(TraceCode::MERCHANT_CREATE_STAKEHOLDER_REQUEST, [
            'merchant_id' => $merchantId,
            'input'       => $input,
        ]);

        $stakeholders = $this->repo->stakeholder->fetchStakeholders($merchantId);
        if ($stakeholders->isNotEmpty() === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_STAKEHOLDER_ALREADY_EXISTS);
        }

        $stakeholder = Tracer::inspan(['name' => HyperTrace::SAVE_STAKEHOLDER], function () use ($merchantId, $input) {

            return $this->saveStakeholder(null, $merchantId, $input);
        });

        return $stakeholder;
    }

    public function saveStakeholder($id, string $merchantId, array $input, string $rule='edit'): Entity
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($id, $merchantId, $input, $rule) {
            $startTime = microtime(true);

            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $merchantDetailInput = Helper::getMerchantDetailInput($input);

            $accountV2Core = new AccountV2\Core;
            $accountV2Validator = new AccountV2\Validator();

            $merchantDetailCore  = new Detail\Core;

            Tracer::inspan(['name' => HyperTrace::VALIDATE_NC_RESPONDED_IF_APPLICABLE], function () use ($accountV2Validator, $merchant, $merchantDetailInput) {

                $accountV2Validator->validateNeedsClarificationRespondedIfApplicable($merchant, $merchantDetailInput);
            });

            $accountV2Validator->validateOptionalFieldSubmissionInActivatedKycPendingState($merchant, $merchantDetailInput);

            if (empty($merchantDetailInput) === false)
            {
                Tracer::inspan(['name' => HyperTrace::SAVE_MERCHANT_DETAILS], function () use ($merchantDetailCore, $merchantDetailInput, $merchant) {

                    $merchantDetailCore->saveMerchantDetails($merchantDetailInput, $merchant);
                });

                $merchantDetails = $merchant->merchantDetail;

                Tracer::inspan(['name' => HyperTrace::UPDATE_NC_FIELDS_ACKNOWLEDGED], function () use ($accountV2Core, $merchantDetailInput, $merchant) {

                    $accountV2Core->updateNCFieldsAcknowledgedIfApplicable($merchantDetailInput, $merchant);
                });

                $this->trace->info(TraceCode::UPDATED_KYC_CLARIFICATION_REASONS, [
                    'merchant_id'                   => $merchantId,
                    'stakeholder_id'                => $id,
                    'kyc_clarification_reasons'     => $merchantDetails->getKycClarificationReasons() ?? []
                ]);

                AutoUpdateMerchantProducts::dispatch(Product\Status::STAKEHOLDER_SOURCE, $merchantId);
            }

            $stakeholderUpdateStartTime = microtime(true);

            $stakeholderInput = Helper::getStakeholderInput($input);

            $stakeholder = Tracer::inspan(['name' => HyperTrace::CREATE_OR_FETCH_STAKEHOLDER], function () use ($id, $merchantId, $merchant, $merchantDetailCore) {

                if (empty($id) === false)
                {
                    $stakeholder = $this->repo->stakeholder->findByIdAndMerchantId($id, $merchantId);
                }
                else
                {
                    $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);
                    $stakeholder = $this->createOrFetchStakeholder($merchantDetails);
                }
                return $stakeholder;
            });

            Tracer::inspan(['name' => HyperTrace::EDIT_STAKEHOLDER], function () use ($stakeholder, $stakeholderInput, $rule) {

                $this->editStakeholder($stakeholder, $stakeholderInput, $rule);
            });

            $this->trace->info(TraceCode::STAKEHOLDER_V2_UPDATE_LATENCY, [
                'stakeholder_update_startTime'         => $stakeholderUpdateStartTime,
                'stakeholder_update_duration'          => (microtime(true) - $stakeholderUpdateStartTime) * 1000,
                'stakeholder_update_overall_duration'  => (microtime(true) - $startTime) * 1000,
                'stakeholder_update_overall_startTime' => $startTime * 1000,
                'merchant_id'                          => $merchantId,
                'stakeholder_id'                       => $id
            ]);
            return $stakeholder;
        });
    }

    public function fetch(string $merchantId, string $id): Entity
    {
        return $this->repo->stakeholder->findByIdAndMerchantId($id, $merchantId);
    }

    public function fetchAll(string $merchantId): Base\PublicCollection
    {
        return $this->repo->stakeholder->fetchStakeholders($merchantId);
    }

    public function update(string $merchantId, string $id, array $input): Entity
    {
        $this->trace->info(TraceCode::MERCHANT_UPDATE_STAKEHOLDER_REQUEST, [
            'merchant_id' => $merchantId,
            'input'       => $input,
            'id'          => $id,
        ]);

        return Tracer::inspan(['name' => HyperTrace::SAVE_STAKEHOLDER], function () use ($id, $merchantId, $input) {

            return $this->saveStakeholder($id, $merchantId, $input);
        });
    }

    public function editStakeholder(Entity $stakeholder, $input, $rule='edit')
    {
        if (isset($input[Constants::ADDRESSES]) === true)
        {
            $addressCore = new Address\Core;

            foreach ($input[Constants::ADDRESSES] as $addressArr)
            {
                $address = $this->repo->address->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, $addressArr[Address\Entity::TYPE]);

                if (empty($address) === true)
                {
                    $addressCore->create($stakeholder, $stakeholder->getEntity(), $addressArr);
                }
                else
                {
                    $addressCore->edit($address, $addressArr);
                }
            }
        }

        if (empty($input[Constants::VERIFICATION_METADATA]) === false)
        {
            $input[Constants::VERIFICATION_METADATA] = $this->mergeJson($stakeholder->getVerificationMetadata(), $input[Constants::VERIFICATION_METADATA]);
        }

        unset($input[Constants::ADDRESSES]);

        $stakeholder->edit($input, $rule);

        $this->repo->saveOrFail($stakeholder);
    }

    /**
     * Fetch stakeholder if exists else creates one
     *
     * @param Detail\Entity $merchantDetails
     * @return Entity
     */
    public function createOrFetchStakeholder(Detail\Entity $merchantDetails)
    {
        $stakeholder = $merchantDetails->stakeholder;

        if ($stakeholder === null)
        {
            $this->trace->info(
                TraceCode::STAKEHOLDER_DOES_NOT_EXIST,
                [
                    'merchant_id' => $merchantDetails->getMerchantId(),
                ]
            );

            $stakeholder = $this->createStakeholderFromMerchantDetails($merchantDetails);

            $merchantDetails->setRelation(Detail\Entity::STAKEHOLDER, $stakeholder);
        }

        return $stakeholder;
    }

    public function checkIfStakeholderExists(Detail\Entity $merchantDetails)
    {
        $stakeholder = $merchantDetails->stakeholder;

        return (empty($stakeholder) === false);
    }

    protected function createStakeholderFromMerchantDetails(Detail\Entity $merchantDetails): Entity
    {
        $mutexResource = self::STAKEHOLDER_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function () use ($merchantDetails) {

            // this is required if another thread gets the lock immediately
            // after the previous thread releases the lock. So we refresh the relation and if found, we return
            $merchantDetails->load(Detail\Entity::STAKEHOLDER);

            $stakeholder = $merchantDetails->stakeholder;

            if (empty($stakeholder) === false)
            {
                return $stakeholder;
            }

            $stakeholder = new Entity;

            $stakeholder->generateId();

            $fields = Constants::MERCHANT_DETAILS_COMMON_FIELDS;

            $stakeholderInput = [];

            foreach ($fields as $key => $val)
            {
                if (empty($merchantDetails[$val]) === false)
                {
                    $stakeholderInput[$key] = $merchantDetails[$val];
                }
            }

            $this->trace->info(TraceCode::MERCHANT_CREATE_STAKEHOLDER_DETAILS, $stakeholderInput);

            $stakeholder->build($stakeholderInput);

            $this->repo->stakeholder->saveOrFail($stakeholder);

            return $stakeholder;
        });
    }

    /**
     * edit stakeholder if exists else creates one
     *
     * @param Detail\Entity $merchantDetails
     * @param               $stakeholderInput
     */
    public function createOrEditStakeholder(Detail\Entity $merchantDetails, $stakeholderInput)
    {
        $stakeholder = $merchantDetails->stakeholder;

        if ($stakeholder === null)
        {
            $this->trace->info(
                TraceCode::STAKEHOLDER_DOES_NOT_EXIST,
                [
                    'merchant_id' => $merchantDetails->getMerchantId(),
                ]
            );

            $stakeholder = $this->createStakeholderFromMerchantDetails($merchantDetails);

            $merchantDetails->setRelation(Detail\Entity::STAKEHOLDER, $stakeholder);
        }

        $this->editStakeholder($stakeholder, $stakeholderInput);

    }

    protected function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }

        return $existingDetails;
    }

    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::MERCHANT_ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::MERCHANT_ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === MerchantConstants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                $stakeholders = (new Repository())->fetchStakeholders($data["merchant_id"]);

                if ($stakeholders->isNotEmpty() === true)
                {
                    unset($data["merchant_id"]);

                    foreach ($stakeholders as $stakeholder)
                    {
                        $stakeholder->edit($data);

                        $this->repo->saveOrFail($stakeholder);
                    }
                }
                else
                {
                    $stakeholder = new Entity;

                    $stakeholder->generateId();

                    $stakeholder->build($data);

                    $this->repo->stakeholder->saveOrFail($stakeholder);

                }
            }
        }
    }
}
