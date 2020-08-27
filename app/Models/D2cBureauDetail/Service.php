<?php

namespace RZP\Models\D2cBureauDetail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Services\Mutex;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Constants\Product;
use RZP\Models\D2cBureauReport;

class Service extends Base\Service
{
    /** @var D2cBureauReport\Service  */
    protected $bureauReport;

    /** @var Mutex */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->bureauReport = new D2cBureauReport\Service();
    }

    public function getOrCreate(array $input): array
    {
        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $this->app['basicauth']->setMerchant($this->merchant);

            $this->user = $this->repo->user->findOrFail($input[Entity::USER_ID]);
        }

        $this->trace->info(TraceCode::D2C_BUREAU_DETAILS_CREATE, [
            'merchant_id'   => $this->merchant->getId(),
            'user_id'       => $this->user->getId(),
        ]);

        $merchantUserMapping = $this->repo->merchant->getMerchantUserMapping($this->merchant->getId(),
                                                                             $this->user->getId(),
                                                                             null,
                                                                             Product::PRIMARY);

        if ($merchantUserMapping->pivot['role'] !== User\Role::OWNER)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_D2C_NON_OWNER_USER_NOT_ALLOWED,
                    'role',
                    $merchantUserMapping->pivot['role']);
        }

        $merchantDetails = $this->merchant->merchantDetail;

        try
        {
           return $this->core()->getOrCreate($merchantDetails, $this->merchant, $this->user, $input)->toArrayPublic();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\RuntimeException('Error occured while fetching owner details', [
                                                    'merchant_id'   => $this->merchant->getId(),
                                                    'user_id'       => $this->user->getId(),
                                                ],
                                                $e);
        }

    }

    public function updateDetails($id, array $input): array
    {
        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $this->app['basicauth']->setMerchant($this->merchant);

            $this->user = $this->repo->user->findOrFail($input[Entity::USER_ID]);

            $input = $input['data'];
        }

        $this->trace->info(TraceCode::D2C_BUREAU_DETAILS_UPDATE, [
            'id'    => $id,
            'input' => $input,
        ]);

        /** @var Entity $bureauDetail */
        $bureauDetail = $this->repo->d2c_bureau_detail->findByPublicIdAndMerchant($id, $this->merchant);

        $contactMobile = $bureauDetail->getContactMobile();

        $bureauDetail->edit($input);

        if ((isset($input[Entity::CONTACT_MOBILE]) === true) &&
            ($input[Entity::CONTACT_MOBILE] !== $contactMobile))
        {
            $bureauDetail[Entity::STATUS] = Status::CREATED;

            $bureauDetail->setVerifiedAtNull();
        }

        $this->repo->saveOrFail($bureauDetail);

        (new JitValidator)->rules(Validator::$afterPatchRules)
                          ->caller($this)
                          ->input($bureauDetail->toArray())
                          ->strict(false)
                          ->validate();

        return $bureauDetail->toArrayPublic();
    }

    public function getReportWithOtp($id, array $input): array
    {
        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $this->app['basicauth']->setMerchant($this->merchant);

            $this->user = $this->repo->user->findOrFail($input[Entity::USER_ID]);
        }

        $traceData = $input;

        $traceData['otp'] = '**redacted** length:' . strlen($input['otp'] ?? '');

        $this->trace->info(TraceCode::D2C_BUREAU_OTP_SUBMIT_REQUEST, [
            'id'    => $id,
            'input' => $traceData,
        ]);

        /** @var Entity $bureauDetail */
        $bureauDetail = $this->repo->d2c_bureau_detail->findByPublicIdAndMerchant($id, $this->merchant);

        (new JitValidator)->rules(Validator::$afterPatchRules)
                          ->caller($this)
                          ->input($bureauDetail->toArray())
                          ->strict(false)
                          ->validate();

        $this->user->validateInput('verifyOtp', [
                                        'otp'               => $input['otp'],
                                        'token'             => $input['token'],
                                        'action'            => 'bureau_verify',
                                        'contact_mobile'    => $bureauDetail->getContactMobile(),
                                    ]);

        return $this->mutex->acquireAndRelease($bureauDetail->getId(), function () use ($bureauDetail, $input)
        {
            (new User\Core)->verifyOtp([
                                            'otp'               => $input['otp'],
                                            'token'             => $input['token'],
                                            'action'            => 'bureau_verify',
                                            'contact_mobile'    => $bureauDetail->getContactMobile(),
                                      ],
                                      $this->merchant,
                                      $this->user);

            $this->core()->updateStatusVerified($bureauDetail);

            $report = $this->bureauReport->getReport($bureauDetail, $this->merchant, $this->user);

            return $this->repo->transactionOnLiveAndTest(function() use ($bureauDetail, $input, $report)
            {
                // every bureau credit score pull cost us some money. So removing feature after pulling score once so
                // that dashboard doesn't fetch score again.
                $this->removeFeature();

                $maxLoanAmount = $bureauDetail->getMaxLoanAmount();

                if (is_string($maxLoanAmount) === false)
                {
                    // incase there's no setting present for given entity_id, entity_type & module, setting accessor
                    // returns [] instead of null. so to maintain consistency in api response format, changing anything
                    // other than string to null.
                    $maxLoanAmount =  null;
                }
                else
                {
                    $maxLoanAmount = (int) $maxLoanAmount;
                }

                return $report->toArrayForDashboard() + [
                        'max_loan_amount'   => $maxLoanAmount,
                    ];
            });
        },
        120);
    }

    public function createReportForLos($input)
    {
        if ((empty($input[Entity::MERCHANT_ID]) === true) or
            (empty($input[Entity::USER_ID]) === true))
            {
                throw new Exception\BadRequestValidationFailureException('user_id & merchant_id are required');
            }

        $this->merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

        $this->app['basicauth']->setMerchant($this->merchant);

        $this->user = $this->repo->user->findOrFail($input[Entity::USER_ID]);

        $data = $input['d2c_bureau_detail'];

        $merchantDetails = $this->merchant->merchantDetail;

        (new JitValidator)->rules(Validator::$afterPatchRules)
                          ->caller($this)
                          ->input($data)
                          ->strict(false)
                          ->validate();

        /** @var Entity $bureauDetail */
        $bureauDetail = $this->core()->getOrCreate($merchantDetails, $this->merchant, $this->user, $data);


        if ((isset($data[Entity::CONTACT_MOBILE]) === true) &&
            ($data[Entity::CONTACT_MOBILE] !== $bureauDetail->getContactMobile()))
        {
            $bureauDetail[Entity::STATUS] = Status::CREATED;

            $bureauDetail->setVerifiedAtNull();
        }

        $bureauDetail->edit($data);

        $this->repo->saveOrFail($bureauDetail);

        $this->user->validateInput('verifyOtp', [
            'otp'               => $input['otp'],
            'token'             => $input['token'],
            'action'            => 'bureau_verify',
            'contact_mobile'    => $bureauDetail->getContactMobile(),
        ]);

        return $this->mutex->acquireAndRelease($bureauDetail->getId(), function () use ($bureauDetail, $input)
        {
            try
            {
                (new User\Core)->verifyOtp([
                    'otp'               => $input['otp'],
                    'token'             => $input['token'],
                    'action'            => 'bureau_verify',
                    'contact_mobile'    => $bureauDetail->getContactMobile(),
                ], $this->merchant, $this->user);
            }
            catch (\Throwable $e)
            {
                if ($e->getCode() === ErrorCode::BAD_REQUEST_INCORRECT_OTP)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_D2C_WRONG_OTP, null, $e->getData()); 
                }

                throw $e;
            }

            $this->core()->updateStatusVerified($bureauDetail);

            $report =  $this->bureauReport->getReport($bureauDetail, $this->merchant, $this->user);

            return $this->bureauReport->getReportArrayForLos($report);
        }, 120);
    }

    private function removeFeature()
    {
        (new Merchant\Service)->addOrRemoveMerchantFeatures([
            'features' => [
                \RZP\Models\Feature\Constants::SHOW_CREDIT_SCORE => '0',
            ],
            \RZP\Models\Feature\Entity::SHOULD_SYNC => true,
        ]);
    }
}
