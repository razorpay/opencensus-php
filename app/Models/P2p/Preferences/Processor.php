<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Models\Order;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Models\BankAccount as BankAccount;
use RZP\Exception\P2p\BadRequestException;
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

        $preferencesResponse = null;

        $preferencesResponse =  array_merge($this->getGatewayPreferencesForSDK(), $this->getSDKVersionLimitations());

        if(isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $preferencesResponse = array_merge($this->getCustomerData((new Device\Core)->getDeviceCustomer($input[Entity::CUSTOMER_ID])),
                                   $this->getGatewayPreferencesForSDK(), $this->getSDKVersionLimitations());
        }


        // if order id and customer id are empty
        if(isset($input[Entity::ORDER_ID]) === false and (isset($input[Entity::CUSTOMER_ID]) === false))
        {
            return $preferencesResponse;
        }

        if(isset($input[Entity::CUSTOMER_ID]) === true)
        {
            CustomerEntity::verifyIdAndSilentlyStripSign($input[Entity::CUSTOMER_ID]);
        }

        // If TPV is enabled for the merchant
        if($this->context()->getMerchant()->isTPVRequired() === true)
        {
            // marking is tpv flag as true
            $preferencesResponse[Entity::IS_TPV] = true;

            $preferencesResponse[Entity::TPV] = $this->getTPVContents($input);
        }

        return $preferencesResponse;
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

    /**
     * Get TPV contents
     * @param array $input
     *
     * @return array
     */
    private function getTPVContents(array $input)
    {
        $CUSTOMER_BANKACCOUNT_FETCH_LIMIT = 10;

        $tpvContents = [
            Entity::RESTRICT_BANK_ACCOUNTS => true,
        ];

        // order id  will always be given preference over customer id
        // if order id is passed to tpv contents then pull bank accounts for the order id
        if(isset($input[Entity::ORDER_ID]) === true)
        {
            $order = (new Order\Core)->findByPublicIdAndMerchant($input[Entity::ORDER_ID], $this->context()->getMerchant());

            $bank_account_array[0] = $order->bankAccount->toArray();

            // this check is only done for tpv merchants
            // if its a tpv order and bank account is not passed exception will be thrown
            if ((isset($bank_account_array) != true) or
                (count($bank_account_array) === 0))
            {
                throw new \RZP\Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT, Entity::ID,
                 [
                     'id' => $input[Entity::ORDER_ID]
                 ]);
            }

            $tpvContents = array_merge($tpvContents,[Entity::BANK_ACCOUNTS => $this->getBankAccountContentsForPreferences($bank_account_array)]);

            return $tpvContents;

        }// if customer id is passed to tpv contents then pull bank accounts for the customer id
        else if(isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $bankAccounts = (new BankAccount\Core)->fetchCustomerBankAccountByCustomerIdAndMerchantId(CustomerEntity::stripDefaultSign($input[Entity::CUSTOMER_ID]),
                              $this->context()->getMerchant()->getId(), $CUSTOMER_BANKACCOUNT_FETCH_LIMIT);

            $tpvContents = array_merge($tpvContents,[Entity::BANK_ACCOUNTS => $this->getBankAccountContentsForPreferences($bankAccounts->toArray())]);

        }

        return $tpvContents;
    }


    public function getBankAccountContentsForPreferences(array $bank_account_array)
    {
        $bank_account_contents = [];

        foreach($bank_account_array as $bankaccount)
        {
            $bank_account_content = [];

            $bank_account_content[BankAccount\Entity::IFSC] = $bankaccount[BankAccount\Entity::IFSC];

            $bank_account_content[BankAccount\Entity::ACCOUNT_NUMBER] = (new Order\Core)->getMaskedAccountNumber($bankaccount[BankAccount\Entity::ACCOUNT_NUMBER]);

            $bank_account_content[BankAccount\Entity::BANK_NAME] = $bankaccount[BankAccount\Entity::BANK_NAME];

            array_push($bank_account_contents,$bank_account_content);
        }

        return $bank_account_contents;
    }
}
