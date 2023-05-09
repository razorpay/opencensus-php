<?php


namespace RZP\Models\Merchant\Store;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Detail\Constants as DEConstant;

class UPITerminalProcurementStatusBannerKey extends Base\Core
{
    public function getValue(string $merchantId, string $namespace, string $key)
    {
        $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

        $data[$key] = $store->get($merchantId, $namespace, $key);

        if ($data[$key] === null)
        {
            $this->getUpdatedTerminalBannerStatus($data, $key, $merchantId);
        }

        return $data[$key];
    }

    /**
     * @param array  $data
     * @param string $key
     * @param string $merchantId
     *
     * @return void
     */
    private function getUpdatedTerminalBannerStatus(array &$data, string $key, string $merchantId)
    {
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

            return;
        }

        $isUPIPaymentMethodEnabled = $this->repo->methods->isUpiEnabledForMerchant($merchantId);

        $acceptedTimeInterval = Carbon::now()->subMinutes(10)->getTimestamp();

        $terminalRequestedTime = $actionState['created_at'];

        if ($terminalRequestedTime < $acceptedTimeInterval and $isUPIPaymentMethodEnabled === false)
        {
            $status = DEConstant::PENDING;

            $updatedTerminalStatusBannerData = [
                Constants::NAMESPACE                              => ConfigKey::ONBOARDING_NAMESPACE,
                ConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => $status,
            ];

            $data[$key] = $status;

            (new StoreCore())->updateMerchantStore($merchantId, $updatedTerminalStatusBannerData);
        }
    }
}
