<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Customer\Entity as CustomerEntity;

/**
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    /**
     * This is a function to get preferences for a razorpay SDK to choose client side bank sdk
     * This will be generalized more later with multi bank setup
     * @param array $input
     *
     * @return array
     */
    public function getPreferences(array $input): array
    {
        $this->initialize(Action::GET_PREFERENCES, $input);

        $customer = (new Device\Core)->getDeviceCustomer($input[Entity::CUSTOMER_ID]);

        return array_merge($this->getCustomerData($customer), $this->getGatewayPreferencesForSDK());
    }

    private function getCustomerData(CustomerEntity $customer)
    {
        return [
            Entity::CUSTOMER => [
                    CustomerEntity::NAME => $customer->getName(),
                ]
            ];
    }

    private function getGatewayPreferencesForSDK()
    {
        $adminService = new AdminService;

        $popularBankList = $adminService->getConfigKey(['key' => ConfigKey::UPI_TURBO_POPULAR_BANK_LIST]);

        return[
            Entity::GATEWAYS => [
                [
                    Entity::PRIORITY   => '0',
                    Entity::GATEWAY    => $this->getGateway(),
                ],
            ],
            Entity::POPULAR_BANKS   => $popularBankList,
        ];
    }

}
