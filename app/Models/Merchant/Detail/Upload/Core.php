<?php


namespace RZP\Models\Merchant\Detail\Upload;

use Throwable;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing;
use RZP\Constants\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\User\Service as UserService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Core as MDetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Upload\Processors\Factory;

class Core extends Base\Core
{
    protected $mutex;

    protected $userService;

    protected $auth;

    /**
     * @var MDetailCore
     */
    private $merchantDetailCore;

    /**
     * @var BusinessDetail\Service
     */
    private $businessDetailService;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->auth = $this->app['basicauth'];

        $this->userService = new UserService();

        $this->merchantDetailCore = new MDetailCore();

        $this->businessDetailService = new BusinessDetail\Service();
    }

    public function uploadMerchant(array $input)
    {
        (new Validator)->validateInput('uploadMerchant', $input);

        $format = $input[Constants::FORMAT];

        $parser = Factory::getInstance($format);

        $merchantDetailsInput = $parser->parse($input[Constants::FILE]);

        return $this->mutex->acquireAndRelease(
            $merchantDetailsInput['contact_email'],
            function () use ($merchantDetailsInput) {

                return $this->repo->transactionOnLiveAndTest(function () use(
                    $merchantDetailsInput
                ) {
                    $user = $this->createUser(
                        $merchantDetailsInput['contact_email'],
                        $merchantDetailsInput['business_name']
                    );

                    $merchant = $this->createMerchant(
                        $user, $merchantDetailsInput['contact_email'],
                        $merchantDetailsInput['business_name']
                    );

                    $merchantDetailsInput[DetailEntity::SUBMIT] = '1';

                    return $this->merchantDetailCore->saveMerchantDetails($merchantDetailsInput, $merchant);
                });
        });
    }

    /**
     * *
     * Create merchant and save required/activation details.
     *
     * @param array $entry
     * @return array
     * @throws Throwable
     * @throws LogicException
     */
    public function processMerchantEntry(array $entry): array
    {
        (new Validator)->validateRequestInput($entry);

        $lockKey = $entry[Header::MIQ_CONTACT_EMAIL];

        return $this->mutex->acquireAndRelease($lockKey, function () use ($entry)
        {
            $parser = Factory::getInstance(Constants::BULK_UPLOAD_MIQ);

            $this->trace->info(TraceCode::BATCH_SERVICE_UPLOAD_MIQ_CREATE_REQUEST, [
                    'entry'      =>     $parser->getMaskedEntryForLogging($entry),
                ]
            );

            // Creating a copy of entry for preprocessing
            $processedEntry = array_slice($entry, 0);

            $parser->preProcessMerchantEntry($processedEntry);

            $merchant = $this->repo->transactionOnLiveAndTest(function () use ($processedEntry, $parser, &$entry)
            {
                $user = $this->createUser($processedEntry[Header::MIQ_CONTACT_EMAIL], $processedEntry[Header::MIQ_MERCHANT_NAME]);

                $merchant = $this->createMerchant($user, $processedEntry[Header::MIQ_CONTACT_EMAIL], $processedEntry[Header::MIQ_MERCHANT_NAME],
                    $processedEntry[MerchantEntity::ORG_ID]);

                $feeBearer = $parser->getMerchantFeeBearerType($processedEntry);

                $this->setFeeModelAndFeeBearer($merchant, $processedEntry, $feeBearer);

                if(empty($merchant) === true)
                {
                    throw new Exception\RuntimeException("Failed to create merchant", null,
                        null, ErrorCode::SERVER_ERROR);
                }

                $entry[Header::MIQ_OUT_MERCHANT_ID] = $merchant->getId();

                $entry[Header::MIQ_OUT_FEE_BEARER] = $merchant->getFeeBearer();

                $merchantDetailsInput = $parser->getMerchantDetailInput($processedEntry);

                $this->merchantDetailCore->saveMerchantDetails($merchantDetailsInput, $merchant);

                $websiteDetails = $parser->getWebsiteDetailInput($processedEntry);

                $this->businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $websiteDetails);

                // The Aadhaar linked status will be validated before form submission if it is set to 1 for Aadhaar linkage.
                // However, as this is not applicable for the banking merchant, hence setting it to 0.
                //
                // The activation form milestone is set to L2 as here we are submitting the KYC form.
                $submitData = [
                    Entity::STAKEHOLDER => [
                        Stakeholder\Entity::AADHAAR_LINKED => 0,
                    ],
                    DetailEntity::ACTIVATION_FORM_MILESTONE => "L2",
                    DetailEntity::SUBMIT => '1',
                ];

                $response = $this->merchantDetailCore->saveMerchantDetails($submitData, $merchant);

                if ($response[DetailEntity::SUBMITTED] === false)
                {
                    $this->trace->info(TraceCode::MERCHANT_ACTIVATION_FORM_SUBMISSION_FAILURE,
                        [
                            'merchant_id' => $merchant->getId(),
                        ]);

                    $entry[Header::STATUS] = Status::FAILURE;

                    $entry[Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                    $entry[Header::ERROR_DESCRIPTION] = 'Failed to submit activation details';
                }

                return $merchant;
            });

            // Creating pricing plan only, when merchant is created and KYC form submitted successfully.
            if(!empty($merchant) && empty($entry[Header::STATUS]))
            {
                try
                {
                    // create forward pricing plan and assign to merchant.
                    $plan = (new Pricing\Core)->create([ Pricing\Entity::PLAN_NAME => $merchant->getId(),
                        Pricing\Entity::RULES => $parser->getPricingRulesInput($processedEntry)
                    ], $merchant->getOrgId()
                    );

                    $merchant->setPricingPlan($plan[0][Pricing\Entity::PLAN_ID]);

                    $this->repo->saveOrFail($merchant);

                    $entry[Header::STATUS] = Status::SUCCESS;
                }
                catch (BaseException $e)
                {
                    $error = $e->getError();

                    $entry[Header::STATUS]            = Status::FAILURE;

                    $entry[Header::ERROR_CODE]        = $error->getPublicErrorCode();

                    $entry[Header::ERROR_DESCRIPTION] = $error->getDescription();
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e, null, TraceCode::MERCHANT_UPLOAD_MIQ_PRICING_PLAN_CREATION_FAILED, [
                        Header::MIQ_CONTACT_EMAIL          => $entry[Header::MIQ_CONTACT_EMAIL],
                        Header::MIQ_OUT_MERCHANT_ID        => $merchant->getId(),
                    ]);

                    $entry[Header::STATUS]   = Status::FAILURE;

                    $entry[Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                    $entry[Header::ERROR_DESCRIPTION] = 'Failed to create pricing plan';
                }
            }

            $this->trace->info(TraceCode::BATCH_SERVICE_UPLOAD_MIQ_CREATE_RESPONSE, [
                    'response'    =>  $parser->getMaskedEntryForLogging($entry),
                ]
            );

            return $entry;
        });
    }

    protected function createUser(string $email, string $businessName)
    {
        $confirm_token = gen_uuid();

        $password = gen_uuid();

        $userInput = [
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'confirm_token'         => $confirm_token,
            'name'                  => $businessName
        ];

        return $this->userService->create($userInput);
    }

    protected function createMerchant($user, string $email, string $businessName, string $orgId = null)
    {
        $merchantInput = [
            'name'  => $businessName,
            'email' => $email,
            'org_id'=> $orgId ?? $this->auth->getOrgId()
        ];

        $merchantData = $this->userService->createMerchantFromUser($merchantInput, $user, '', false, [], false);

        return $this->repo->merchant->findOrFailPublic($merchantData[MerchantEntity::ID]);
    }

    /**
     * Update merchant details if input fields present.
     *
     * @param MerchantEntity $merchant
     * @param array $input
     * @param string $feeBearer
     * @return void
     */
    private function setFeeModelAndFeeBearer(MerchantEntity $merchant, array $input, string $feeBearer): void
    {
        if(empty($input[Header::MIQ_FEE_MODEL]) === false)
        {
            $merchant->setFeeModel($input[Header::MIQ_FEE_MODEL]);
        }

        // default platform gets assigned if nothing explicitly assigned.
        if(empty($feeBearer) === false)
        {
            $merchant->setFeeBearer($feeBearer);
        }

        $this->repo->saveOrFail($merchant);
    }
}
