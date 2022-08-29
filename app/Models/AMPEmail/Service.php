<?php

namespace RZP\Models\AMPEmail;

use Throwable;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Models\Merchant\Detail\Entity as MDEntity ;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\AMPEmail\Entity as AMPEmailEntity;
use RZP\Http\Requests\MailModoL1FormSubmissionRequest;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Detail\Constants as DEConstants;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $trace;

    /**
     * @var Core
     */
    protected $core;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->trace = $this->app[Constants::TRACE];

        $this->mutex = $this->app[Constants::API_MUTEX];
    }


    /**
     *
     * @param MerchantEntity $merchant
     *
     * @return bool
     * @throws Throwable
     */
    public function triggerL1FormForMerchant(MerchantEntity $merchant): bool
    {
        $service = MailService::getInstance();

        if(empty($merchant->getEmail())===true)
        {
            $this->trace->info(TraceCode::AMP_EMAIL_MERCHANT_REQUEST_FAILED,
                               [
                                   "merchantId" => $merchant->getId(),
                                   "email"      => "null"]);

            return false;
        }

        $input = [
            Entity::ENTITY_TYPE => Constants::MERCHANT,
            Entity::ENTITY_ID   => $merchant->getId(),
            Entity::VENDOR      => $service->getVendorName(),
            Entity::TEMPLATE    => Constants::L1,
            Entity::STATUS      => Constants::INITIATED,
            Entity::METADATA    => [
                Constants::EMAIL => $merchant->getEmail()
            ]
        ];

        $ampEmail = $this->core->create($input);

        try
        {
            if (empty($ampEmail) === false)
            {
                $triggerResponse = $service->triggerEmail(new L1EmailRequest($ampEmail, $merchant));

                if (empty($triggerResponse) === false and $triggerResponse->isSuccess())
                {
                    $input = [
                        Entity::STATUS   => Constants::OPEN,
                        Entity::METADATA => [
                            Constants::REFERENCE_ID => $triggerResponse->getId()
                        ]
                    ];

                    $ampEmail = $this->core->edit($ampEmail, $input);

                    return true;
                }
            }

        }
        catch (\Exception $e)
        {
            $input = [
                Entity::STATUS   => Constants::FAILED,
                Entity::METADATA => [
                    Constants::MESSAGE => $e->getMessage()
                ]
            ];

            $ampEmail = $this->core->edit($ampEmail, $input);

            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::AMP_EMAIL_MERCHANT_REQUEST_FAILED,
                                         [
                                             "merchantId" => $merchant->getId()]);

        }

        return false;

    }

    public function validateL1FormToken(MailModoL1FormSubmissionRequest $request): bool
    {

        $token = $request->getToken();

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        try
        {
            $ampEmail = $this->repo->amp_email->findByPublicId($token);

            if (empty($ampEmail) === true or
                $ampEmail->getStatus() <> Constants::OPEN or
                $ampEmail->getEntityType() <> Constants::MERCHANT)
            {
                return false;
            }

            $this->app['trace']->info(TraceCode::MAILMODO_SUBMISSION_REQUEST, [
                'token' => 'validated'
            ]);

            return true;
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     *
     * @param MailModoL1FormSubmissionRequest $request
     *
     * @return void
     * @throws \Exception
     */
    public function submitMailModoL1Form(MailModoL1FormSubmissionRequest $request)
    {
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        if ($this->validateL1FormToken($request) === true)
        {

            $formInput = $request->getInputFields();

            $ampEmail = $this->repo->amp_email->findByPublicId($request->getToken());

            $merchant = $this->repo->merchant->findByPublicId($ampEmail->getEntityId());

            $this->app['basicauth']->setMerchant($merchant);

            $success = true;

            try
            {

                $this->repo->transactionOnLiveAndTest(function() use ($ampEmail, $formInput) {

                    (new \RZP\Models\Merchant\Detail\Service())->saveMerchantDetailsForActivation($formInput);

                    $input = [
                        Entity::STATUS => Constants::CLOSE
                    ];

                    $this->core->edit($ampEmail, $input);

                });

            }
            catch (\Exception $e)
            {
                $success = false;

                $this->trace->traceException($e,
                                             Trace::ERROR,
                                             TraceCode::MAILMODO_SUBMISSION_VALIDATION_FAILED,
                                             [
                                                 'request' => $request]);

            }

            $metrics = [
                "success" => $success
            ];

            $this->trace->count(Metric::MAILMODO_L1_FORM_SUBMISSION, $metrics);

            $formInput["L1Submission_status"] = $success ? Constants::SUCCESS : Constants::FAILED;

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $formInput, SegmentEvent::AMP_EMAIL_L1_SUBMISSION);

            if ($success === true)
            {
                $input = [MDEntity::ACTIVATION_FORM_MILESTONE => DEConstants::L1_SUBMISSION];

                (new \RZP\Models\Merchant\Detail\Service())->saveMerchantDetailsForActivation($input);

            }
            else
            {
                {
                    throw new GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
                }
            }
        }
    }
}
