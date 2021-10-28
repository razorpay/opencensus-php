<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Models\Merchant\Account;
use RZP\Models\QrCode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\QrPaymentRequest\Type;
use RZP\Exception\BadRequestException;

class Core extends QrCode\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->generator = new Generator;
    }

    public function buildQrCode(array $input)
    {
        $qrCode = (new Entity())->build($input);

        $this->checkFeatureEnabled($input);

        $customer = $this->getCustomerIfGiven($input);

        $qrCode->customer()->associate($customer);

        $qrCode->merchant()->associate($this->merchant);

        return $this->build($qrCode);
    }

    private function build(Entity $qrCode)
    {
        $qrCode->generateQrString();

        $this->setShortUrl($qrCode);

        $this->repo->transaction(function() use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);

            $this->generateQrCodeFile($qrCode);
        });

        return $qrCode;
    }

    private function checkFeatureEnabled($input)
    {
        if ($input[Entity::REQ_PROVIDER] === Type::BHARAT_QR)
        {
            $feature = Feature\Constants::BHARAT_QR;

            if ($this->merchant->isFeatureEnabled($feature) === false)
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
        $qrCode->setStatus(Status::CLOSED);

        $currentTime = Carbon::now()->getTimestamp();

        $qrCode->setClosedAt($currentTime);

        $qrCode->setCloseReason($closeReason);

        $this->repo->transaction(function() use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);

            if ($qrCode->bankAccount !== null)
            {
                $this->repo->deleteOrFail($qrCode->bankAccount);
            }
        });

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
            Entity::REQ_USAGE_TYPE => 'multiple_use',
            Entity::FIXED_AMOUNT   => false,
            Entity::REQ_PROVIDER   => 'bharat_qr',
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
