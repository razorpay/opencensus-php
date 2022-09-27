<?php


namespace RZP\Models\Merchant\Detail\Upload;

use Throwable;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Models\User\Service as UserService;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Core as MDetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Upload\Processors\Factory;
use RZP\Models\Merchant\BusinessDetail\Service as BDetailService;

class Core extends Base\Core
{
    protected $mutex;

    protected $userService;

    protected $auth;

    /**
     * @var MerchantCore
     */
    private $merchantCore;

    /**
     * @var MDetailCore
     */
    private $merchantDetailCore;

    /**
     * @var BDetailService
     */
    private $businessDetailService;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->auth = $this->app['basicauth'];

        $this->userService = new UserService();

        $this->merchantCore = new MerchantCore();

        $this->merchantDetailCore = new MDetailCore();

        $this->businessDetailService = new BDetailService();
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
     * @throws BadRequestException
     * @throws LogicException
     */
    public function processMerchantEntry(array $entry): array
    {
        $lockKey = $entry[Header::MIQ_CONTACT_EMAIL];

        return $this->mutex->acquireAndRelease($lockKey, function () use ($entry)
        {
            $parser = Factory::getInstance(Constants::BULK_UPLOAD_MIQ);

            $this->trace->info(TraceCode::BATCH_SERVICE_UPLOAD_MIQ_CREATE_REQUEST, [
                    'entry'      =>     $parser->getMaskedEntryForLogging($entry),
                ]
            );

            $parser->preProcessMerchantEntry($entry);

            $batchResponse = $parser->getDefaultBatchResponse($entry);

            $merchant = $this->repo->transactionOnLiveAndTest(function () use ($entry, $parser, &$batchResponse)
            {
                $user = $this->createUser($entry[Header::MIQ_CONTACT_EMAIL], $entry[Header::MIQ_MERCHANT_NAME]);

                $merchant = $this->createMerchant($user, $entry[Header::MIQ_CONTACT_EMAIL], $entry[Header::MIQ_MERCHANT_NAME]);

                $feeBearer = $parser->getMerchantFeeBearerType($entry);

                $this->updateMerchantIfApplicable($merchant, $entry, $feeBearer);

                // saving merchant category details from business category and sub-category
                $this->merchantCore->autoUpdateCategoryDetails($merchant, $entry[Header::MIQ_BUSINESS_CATEGORY],
                    $entry[Header::MIQ_SUB_CATEGORY], true);

                if(empty($merchant) === true)
                {
                    $batchResponse[Header::STATUS] = Status::FAILURE;

                    $batchResponse[Header::ERROR_DESCRIPTION] = 'Could not create/Find merchant';

                    return null;
                }

                $merchantDetailsInput = $parser->getMerchantDetailInput($entry);

                $this->merchantDetailCore->saveMerchantDetails($merchantDetailsInput, $merchant);

                // saving dummy files, required in merchant activation.
                $this->merchantDetailCore->saveDummyActivationFiles($merchant);

                // submit dummy KYC details
                $submitData = [
                    DetailEntity::SUBMIT   =>   '1'
                ];

                $response = $this->merchantDetailCore->saveMerchantDetails($submitData, $merchant);

                if ($response[DetailEntity::SUBMITTED] === false)
                {
                    $this->trace->info(TraceCode::MERCHANT_ACTIVATION_FORM_SUBMISSION_FAILURE,
                        [
                            'merchant_id' => $merchant->getId(),
                        ]);

                    $batchResponse[Header::STATUS] = Status::FAILURE;

                    $batchResponse[Header::ERROR_DESCRIPTION] = 'Activation details could not be submitted successfully';
                }
                // saving website details, required in merchant activation.
                $websiteDetails = $parser->getWebsiteDetailInput($entry);

                $this->businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $websiteDetails);

                return $merchant;
            });

            if(empty($merchant) === false)
            {
                // set batch response
                $batchResponse[Header::MIQ_OUT_MERCHANT_ID] = $merchant->getId();

                $batchResponse[Header::MIQ_OUT_FEE_BEARER]  = $merchant->getFeeBearer();

                // TODO - pricing automation related codes and then set status success/failure.
                $batchResponse[Header::STATUS] = Status::SUCCESS;
            }

            $this->trace->info(TraceCode::BATCH_SERVICE_UPLOAD_MIQ_CREATE_RESPONSE, [
                    'response'    =>  $batchResponse,
                ]
            );

            return $batchResponse;
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

    protected function createMerchant($user, string $email, string $businessName)
    {
        $merchantInput = [
            'name'  => $businessName,
            'email' => $email,
            'org_id'=> $this->auth->getOrgId()
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
    private function updateMerchantIfApplicable(MerchantEntity $merchant, array $input, string $feeBearer)
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
