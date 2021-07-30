<?php

namespace RZP\Models\FundAccount\DetailsPropagator;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Contact\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

class VendorPaymentDetailsPropagator extends Base
{
    public function update(FundAccountEntity $fundAccount, string $mode = Mode::LIVE)
    {
        try
        {
            $vendorPaymentService = $this->app['vendor-payment'];

            if ($fundAccount->getSourceType() !== ContactEntity::CONTACT)
            {
                return;
            }

            $contact = $this->repo
                ->contact
                ->findByPublicId(
                    ContactEntity::getSignedId($fundAccount->getSourceId())
                );

            if ($contact->getType() !== Type::VENDOR)
            {
                return;
            }

            $data = $fundAccount->toArrayPublic();

            $vendorPaymentService->pushFundAccountDetails($data, $mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB_ERROR,
                [
                    Core::FUND_ACCOUNT_ID  => $fundAccount->getId(),
                    MerchantEntity::MERCHANT_ID         => $fundAccount->getMerchantId()
                ]);
        }
    }
}
