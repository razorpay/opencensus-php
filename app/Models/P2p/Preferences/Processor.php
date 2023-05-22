<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
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
        if(isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customer = (new Device\Core)->getDeviceCustomer($input[Entity::CUSTOMER_ID]);

            return array_merge($this->getCustomerData($customer), $this->getGatewayPreferencesForSDK(), $this->getSDKVersionLimitations());
        }
        else
        {
            return array_merge($this->getGatewayPreferencesForSDK(), $this->getSDKVersionLimitations());
        }
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
        return [
            Entity::GATEWAYS => [
                [
                    Entity::PRIORITY   => '0',
                    Entity::GATEWAY    => $this->getGateway(),
                ],
            ],
            Entity::POPULAR_BANKS   => Constants::getPopularBanksList(),
        ];
    }

    private function getSDKVersionLimitations()
    {
        return [
            Entity::SDK_VERSIONS => [
                Entity::ANDROID => [
                    Entity::MIN       => '1.0.0',
                    Entity::BLOCKED   => ['1.1.0', '1.1.1', '1.2.0'],
                ],
                Entity::IOS     => [
                    Entity::MIN       => '1.0.0',
                    Entity::BLOCKED   => ['1.1.0', '1.1.1', '1.2.0'],
                ],
            ],
        ];
    }

}
