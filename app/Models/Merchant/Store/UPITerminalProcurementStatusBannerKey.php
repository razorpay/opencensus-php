<?php


namespace RZP\Models\Merchant\Store;

use Carbon\Carbon;
use Exception;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Merchant\Methods\Core as MethodsCore;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Detail\Constants as DEConstant;

class UPITerminalProcurementStatusBannerKey extends Base\Core
{
    /**
     * @param string $merchantId
     * @param string $namespace
     * @param string $key
     * @return string
     * @throws LogicException
     */
    public function getValue(string $merchantId, string $namespace, string $key)
    {
        try
        {
            $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

            $data[$key] = $store->get($merchantId, $namespace, $key);

            $updatedBannerKey = $this->getUpdatedTerminalBannerStatus($data, $key, $merchantId);

            if (empty($updatedBannerKey) === false) {
                $data[$key] = $updatedBannerKey;

                $updatedTerminalStatusBannerData = [
                    Constants::NAMESPACE => ConfigKey::ONBOARDING_NAMESPACE,
                    ConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => $updatedBannerKey,
                ];

                (new StoreCore())->updateMerchantStore($merchantId, $updatedTerminalStatusBannerData);
            }

            return $data[$key];

        } catch (Exception $e) {
            $this->trace->traceException($e);
            throw $e;
        }
    }

    /**
     * @param array $data
     * @param string $key
     * @param string $merchantId
     *
     * @return string
     * @throws Exception
     */
    private function getUpdatedTerminalBannerStatus(array &$data, string $key, string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $isUPIPaymentMethodAllowed = (new MethodsCore())->isUPIPaymentMethodAllowed($merchant);

        if ($isUPIPaymentMethodAllowed === false) {
            return DEConstant::NO_BANNER;
        }

        $name = [
            Status::ACTIVATED_MCC_PENDING,
            Status::ACTIVATED
        ];

        $actionState = $this->repo->state->fetchByEntityIdAndEntityTypeAndName($merchantId, $name);

        if (empty($actionState) === true)
        {
            $this->trace->info(TraceCode::TERMINAL_NOT_REQUESTED, [
                'merchant_id' => $merchantId,
                'reason'      => 'No entry for merchant in AMP state',
            ]);

            //Empty string is not equivalent to NO_BANNER. The latter is a terminal state whereas the former is not.
            return "";
        }

        $isUPIPaymentMethodEnabled = $this->repo->methods->isUpiEnabledForMerchant($merchantId);

        if ($isUPIPaymentMethodEnabled === true and $merchant->merchantDetail->getActivationStatus() === Status::ACTIVATED) {
            if ($data[$key] === DEConstant::PENDING or $data[$key] === DEConstant::NO_BANNER) {
                return DEConstant::NO_BANNER;
            }
            else {
                return DEConstant::SUCCESS;
            }
        }

        $acceptedTimeInterval = Carbon::now()->subMinutes(10)->getTimestamp();

        $terminalRequestedTime = $actionState['created_at'];

        if (empty($data['key'] === true) and $isUPIPaymentMethodEnabled === false and $terminalRequestedTime < $acceptedTimeInterval)
        {
           return DEConstant::PENDING;
        }

        return "";
    }
}
