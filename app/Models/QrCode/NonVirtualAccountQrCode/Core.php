<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Trace\Tracer;
use RZP\Models\QrCode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\HyperTrace;
use RZP\Models\EntityOrigin;
use RZP\Models\Merchant\Account;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\QrPaymentRequest\Type;
use RZP\Models\Order\Entity as Order;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Models\QrPayment\Service as QrPaymentService;
use RZP\Models\Checkout\Order\Entity as CheckoutOrder;

class Core extends QrCode\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->generator = new Generator;
    }


    /**
     * @param array                    $input
     * @param Order|CheckoutOrder|null $order
     *
     * @return mixed|QrCode\Entity
     *
     * @throws BadRequestException
     */
    public function buildQrCode(array $input, $order = null)
    {
        $qrCode = (new Entity())->build($input);

        $this->checkFeatureEnabled($input);

        $customer = $this->getCustomerIfGiven($input);

        $qrCode->customer()->associate($customer);

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($order);

        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE_BUILD_QR_CODE], function () use ($qrCode) {
            return $this->build($qrCode);
        });
        // Creates entity origin when QR code is created
        // QR code creation won't be failed even if origin is not set.
        (new EntityOrigin\Core)->createEntityOrigin($qrCode);

        return $qrCode;

    }

    private function build(Entity $qrCode)
    {
        Tracer::inspan(['name' => HyperTrace::QR_CODE_BUILD_GENERATE_QR_STRING], function () use ($qrCode)
        {
            $qrCode->generateQrString();
        });

        Tracer::inspan(['name' => HyperTrace::QR_CODE_BUILD_SET_SHORT_URL], function () use ($qrCode)
        {
            $this->setShortUrl($qrCode);
        });

        Tracer::inspan(['name' => HyperTrace::QR_CODE_BUILD_SAVE_OR_FAIL], function () use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);
        });


        return $qrCode;
    }

    public function generateQrCodeFile($qrCode)
    {
        if (($qrCode->getRequestSource() === RequestSource::CHECKOUT) or
            ($qrCode->getRequestSource() === RequestSource::FALLBACK))
        {
            return;
        }

        parent::generateQrCodeFile($qrCode);
    }

    public function setShortUrl($qrCode)
    {
        if (($qrCode->getRequestSource() === RequestSource::CHECKOUT) or
            ($qrCode->getRequestSource() === RequestSource::FALLBACK))
        {
            return;
        }

        parent::setShortUrl($qrCode);
    }

    private function checkFeatureEnabled($input)
    {
        if ($input[Entity::REQ_PROVIDER] === Type::BHARAT_QR)
        {
            $isBqrEnabled = $this->merchant->isFeatureEnabled(Feature\Constants::BHARAT_QR_V2);

            if ($isBqrEnabled === false)
            {
                $isBqrEnabled = $this->merchant->isFeatureEnabled(Feature\Constants::BHARAT_QR);
            }

            if ($isBqrEnabled === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_BHARAT_QR_NOT_ENABLED_FOR_MERCHANT);
            }
        }

        if ($input[Entity::REQ_PROVIDER] === Type::UPI_QR)
        {
            $methods = $this->merchant->getMethods();

            if ($methods->isUpiEnabled() === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT);
            }
        }
    }

    public function close($qrCode, $closeReason)
    {
        if (($this->generator->checkIfDedicatedTerminalSplitzExperimentEnabled($qrCode->merchant->getId()) === true) and
            ($qrCode->getUsageType() === UsageType::MULTIPLE_USE))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CLOSE_STATIC_QR_CODE_FAILURE);
        }

        $qrCode->setStatus(Status::CLOSED);

        $currentTime = Carbon::now()->getTimestamp();

        $qrCode->setClosedAt($currentTime);

        $qrCode->setCloseReason($closeReason);

        $vpaId = $this->repo->vpa->findVpaByEntityIdAndEntityType($qrCode->getId(), $qrCode->getEntityName());

        $this->repo->transaction(function() use ($qrCode, $vpaId)
        {
            $this->repo->saveOrFail($qrCode);

            if ($qrCode->bankAccount !== null)
            {
                $this->repo->deleteOrFail($qrCode->bankAccount);
            }

            if ($vpaId !== null)
            {
                $this->repo->vpa->deleteById($vpaId, $qrCode->merchant->getId());
            }
        });

        if ($closeReason !== CloseReason::PAID && $qrCode->isCheckoutQrCode()) {
            // Updating cache only for unpaid & closed QrCodes as we don't have
            // access to PaymentId here
            (new QrPaymentService())->setQrCodeStatusAndPaymentIdInCache($qrCode);
        }

        return $qrCode;
    }

    public function createOrFetchSharedQrCode()
    {
        $fallbackQrCodeId = Entity::SHARED_ID;

        $fallbackQrCode = $this->repo->qr_code->find($fallbackQrCodeId);

        if ($fallbackQrCode === null)
        {
            $fallbackQrCode = $this->createFallbackQrCode();
        }

        return $fallbackQrCode;
    }

    private function createFallbackQrCode()
    {
        $sharedMerchantId = $this->getDefaultMerchantId();

        $this->merchant = $this->repo->merchant->find($sharedMerchantId);

        $input = [
            Entity::REQ_USAGE_TYPE => UsageType::MULTIPLE_USE,
            Entity::FIXED_AMOUNT   => false,
            Entity::REQ_PROVIDER   => Type::UPI_QR,
            Entity::REQUEST_SOURCE => RequestSource::FALLBACK,
        ];

        $qrCode = (new Entity)->build($input);

        $qrCode->setId(Entity::SHARED_ID);

        $qrCode->merchant()->associate($this->merchant);

        return $this->build($qrCode);
    }

    /**
     * For unexpected payments, we use the demo page merchant. This merchant only
     * exists on prod. For other envs, we use the test merchant, i.e. '10000000000000'.
     */
    protected function getDefaultMerchantId()
    {
        $defaultMerchantId = Account::DEMO_PAGE_ACCOUNT;

        if ($this->env !== 'production')
        {
            $defaultMerchantId = Account::TEST_ACCOUNT;
        }

        return $defaultMerchantId;
    }

    protected function getCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo
                             ->customer
                             ->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        return $customer;
    }
}
