<?php


namespace RZP\Models\Payment\Config;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Order as Order;
use RZP\Models\Payment as Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Card\IIN\Repository as IINRepo;

class Validator extends Base\Validator
{
    const BANKS              = 'banks';

    const IINS               = 'iins';

    const PROVIDERS          = 'providers';

    const FLOWS              = 'flows';

    const ISSUERS            = 'issuers';

    const TYPES              = 'types';

    const NETWORKS           = 'networks';

    const WALLETS            = 'wallets';

    const METHOD             = 'method';

    const DURATIONS          = 'durations';

    const ALLOW              = 'allow';

    protected static $createRules = [
        Entity::NAME           => 'required|string|max:255',
        Entity::CONFIG         => 'required|array',
        Entity::IS_DEFAULT     => 'required|boolean',
        Entity::TYPE           => 'required|string|in:checkout',
    ];

    protected static $editRules = [
        Entity::TYPE           => 'required|string|in:checkout',
        Entity::IS_DEFAULT     => 'required_if:type,checkout|boolean',
        Entity::ID             => 'required_if:type,checkout|string',
    ];

    protected static $addRestrictionsRules = [
        self::ALLOW            => 'required|array',
    ];

    protected static $cardRestrictionRules = [
        self::METHOD              =>  'required',
        self::TYPES               =>  'sometimes|array|in:credit,debit',
        self::ISSUERS             =>  'sometimes|custom',
        self::NETWORKS            =>  'sometimes|custom',
        self::IINS                =>  'sometimes|custom',
    ];

    protected static $emiRestrictionRules = [
        self::METHOD               =>  'required',
        self::TYPES                =>  'sometimes|array|in:credit,debit',
        self::ISSUERS              =>  'sometimes|array',
        self::NETWORKS             =>  'sometimes|custom',
        self::IINS                 =>  'sometimes|custom',
        self::DURATIONS            =>  'sometimes|array|in:3,6,9,12,18,24',
    ];

    protected static $upiRestrictionRules = [
        self::METHOD               =>  'required',
        self::FLOWS                =>  'sometimes|array|in:collect,intent,qr',
    ];

    protected static $netbankingRestrictionRules = [
        self::METHOD               =>  'required',
        self::BANKS                =>  'sometimes|custom',
    ];

    protected static $cardlessEmiRestrictionRules = [
        self::METHOD               =>  'required',
        self::PROVIDERS            =>  'sometimes|custom:providersForCardlessEMI',
    ];

    protected static $walletRestrictionRules = [
        self::METHOD               =>  'required',
        self::WALLETS              =>  'sometimes|custom',
    ];

    protected static $paylaterRestrictionRules = [
        self::METHOD              =>  'required',
        self::PROVIDERS           =>  'sometimes|custom:providersForPaylater',
    ];

    const PROPERTIES_TO_VALIDATE_FOR_CARD = [
        self::ISSUERS, self::TYPES, self::NETWORKS, self::IINS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_EMI = [
        self::ISSUERS, self::TYPES, self::NETWORKS, self::IINS, self::DURATIONS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_NETBANKING = [
        self::BANKS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_WALLET = [
        self::WALLETS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_UPI = [
        self::FLOWS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_CARDLESS_EMI = [
        self::PROVIDERS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_PAYLATER = [
        self::PROVIDERS,
    ];

    protected function validateIssuers($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'issuers should be an array');
        }

        foreach ($input as $issuer)
        {
            if ((IFSC::exists($issuer) === false)){
                throw new Exception\BadRequestValidationFailureException(
                    $issuer . ' is not a valid bank code');
            }
        }
    }

    protected function validateNetworks($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'networks should be an array');
        }

        foreach ($input as $network)
        {
            if (Network::isValidNetwork($network) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    $network . ' is not a valid network');
            }
        }

    }

    protected function validateWallets($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'wallets should be an array');
        }

        foreach ($input as $wallet)
        {
            Wallet::validateExists($wallet);
        }
    }

    protected function validateBanks($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'banks should be an array');
        }

        $supportedBanks = Netbanking::getSupportedBanks();

        foreach ($input as $bank)
        {
            if (in_array($bank, $supportedBanks, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    $bank . ' is not a valid bank');
            }
        }
    }

    protected function validateProvidersForCardlessEMI($attribute ,$input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'providers should be an array');
        }

        foreach ($input as $provider)
        {
            if (CardlessEmi::exists($provider) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider is not supported for cardless emi '.$provider,
                    'provider'
                );
            }

        }

    }

    protected function validateProvidersForPaylater($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'providers should be an array');
        }

        foreach ($input as $provider)
        {
            if (PayLater::exists($provider) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider is not supported for Pay Later '.$provider,
                    'provider'
                );
            }
        }
    }

    protected function validateIins($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'iins should be an array');
        }

        foreach ($input as $iin)
        {
            if (strlen($iin) !== 6)
            {
                throw new Exception\BadRequestValidationFailureException(
                  'iin length should be 6'
                );
            }

            $iinEntity = (new IINRepo())->find($iin);

            if (isset($iinEntity) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'iin provided does not exist '.$iin
                );
            }
        }
    }

    public function validatePaymentForConfig($input, $merchant)
    {
        $order_id = $input[Payment\Entity::ORDER_ID];

        $order_repo = new Order\Repository();

        $order = $order_repo->findByPublicIdAndMerchant($order_id, $merchant);

        $config = null;

        $config_repo = new Repository();

        if (isset($order->checkout_config_id) === true)
        {
            $config = $config_repo->findByPublicIdAndMerchant('config_'.$order->checkout_config_id, $merchant);
        }
        else
        {
            $config =$config_repo->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), 'checkout');
        }

        if ($config === null or isset($config->restrictions) === false)
        {
            return;
        }

        $restrictions = json_decode($config->restrictions, true);

        $validRestrictions = $this->getRestrictionForMethod($restrictions, $input[self::METHOD]);

        if (empty($validRestrictions))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_ALLOWED_IN_CONFIG, "method", $input[self::METHOD],
                'Method is not allowed in the config'
            );
        }

        $validateMethod = 'validateFor' . studly_case($input[self::METHOD]);

        foreach ($validRestrictions as $restriction)
        {
            $result = $this->$validateMethod($restriction, $input);

            if ($result === true)
            {
                return true;
            }
        }

        throw new Exception\BadRequestValidationFailureException(
            'The following payment method is not supported for this transaction'
        );
    }

    private function getRestrictionForMethod($restrictions, $method)
    {
        $validRestrictions = [];

        foreach ($restrictions as $key => $restriction)
        {
            if ($restriction[self::METHOD] === $method)
            {
                $validRestrictions[] = $restriction;
            }
        }

        return $validRestrictions;
    }

    private function validateForCard(array $restriction, $input)
    {
        if (isset($input['card']['number']) === false)
        {
            if (isset($input[Payment\Entity::TOKEN]) === true)
            {
                // not supported for saved card as of now
                return true;
            }

            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'card number',
                     null, 'either card number or token should be available');
        }

        $cardNumber = $input['card']['number'];

        $iinRepo = new IINRepo();

        $iinEntity = $iinRepo->find(substr($cardNumber, 0, 6));

        if (isset($iinEntity) === false)
        {
            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'iin', null, 'iin not found');
        }

        foreach (self::PROPERTIES_TO_VALIDATE_FOR_CARD as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($iinEntity, $restriction);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForEmi(array $restriction, $input)
    {
        if (isset($input['card']['number']) === false)
        {
            if (isset($input[Payment\Entity::TOKEN]) === true)
            {
                // not supported for saved card as of now
                return true;
            }

            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'card number',
                null, 'either card number or token should be available');
        }

        $cardNumber = $input['card']['number'];


        $durations = $input['emi_duration'];

        $iinRepo = new IINRepo();

        $iinEntity = $iinRepo->find(substr($cardNumber, 0, 6));

        if (isset($iinEntity) === false)
        {
            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'iin', null, 'iin not found');
        }

        foreach (self::PROPERTIES_TO_VALIDATE_FOR_EMI as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            if ($property === self::DURATIONS)
            {
                $validateResult = $this->$validateMethod($restriction, $durations);
            }
            else
            {
                $validateResult = $this->$validateMethod($iinEntity, $restriction);
            }

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForNetbanking(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_NETBANKING as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($restriction, $input);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForWallet(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_WALLET as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($restriction, $input);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForUpi(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_UPI as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($restriction, $input);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForCardlessEmi(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_CARDLESS_EMI as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($input, $restriction);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForPaylater(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_PAYLATER as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($input, $restriction);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validatePropertyIssuers($iinEntity, $restriction)
    {
        if (isset($restriction[self::ISSUERS]) === false)
        {
            return;
        }
        $allowedIssuers = $restriction[self::ISSUERS];

        $paymentIssuer = $iinEntity->issuer;

        if (in_array($paymentIssuer, $allowedIssuers) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyNetworks($iinEntity, $restriction)
    {
        if (isset($restriction[self::NETWORKS]) === false)
        {
            return;
        }

        $allowedNetworks = $restriction[self::NETWORKS];

        $paymentNetwork  = $iinEntity->network;

        if (in_array($paymentNetwork, $allowedNetworks) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyTypes($iinEntity, $restriction)
    {
        if (isset($restriction[self::TYPES]) === false)
        {
            return;
        }

        $allowedTypes = $restriction[self::TYPES];

        $paymentType  = $iinEntity->type;

        if (in_array($paymentType, $allowedTypes) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyIins($iinEntity, $restriction)
    {
        if (isset($restriction[self::IINS]) === false)
        {
            return;
        }

        $allowedIins = $restriction[self::IINS];

        $paymentIin  = $iinEntity->iin;

        if (in_array($paymentIin, $allowedIins) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyDurations($restriction, $paymentEmiDuration)
    {
        if (isset($restriction[self::DURATIONS]) === false)
        {
            return;
        }

        $allowedDurations = $restriction[self::DURATIONS];

        if (in_array($paymentEmiDuration, $allowedDurations) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyBanks($restriction, $input)
    {
        if (isset($restriction[self::BANKS]) === false)
        {
            return;
        }

        $allowedBanks = $restriction[self::BANKS];

        $paymentBank = $input['bank'];

        if (in_array($paymentBank, $allowedBanks) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyWallets($restriction, $input)
    {
        if (isset($restriction[self::WALLETS]) === false)
        {
            return;
        }

        $allowedWallets = $restriction[self::WALLETS];

        $paymentWallet = $input['wallet'];

        if (in_array($paymentWallet, $allowedWallets) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyProviders($restriction, $input)
    {
        if (isset($restriction[self::PROVIDERS]) === false)
        {
            return;
        }

        $allowedProviders = $restriction[self::PROVIDERS];

        $paymentProvider = $input['provider'];

        if (in_array($paymentProvider, $allowedProviders) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyFlows($restriction, $input)
    {
        if (isset($restriction[self::FLOWS]) === false)
        {
            return;
        }

        $allowedFlows = $restriction[self::FLOWS];

        if (isset($input['upi']) === false or isset($input['upi']['flow']) === false)
        {
            return false;
        }

        $paymentFlow = $input['upi']['flow'];

        if (in_array($paymentFlow, $allowedFlows) === false)
        {
            return false;
        }

        return true;
    }

    public function validateRestrictionJson($allow, $merchant)
    {
        if (gettype($allow) !== 'array')
        {
            throw new Exception\BadRequestValidationFailureException(
                'allow field should be array'
            );
        }

        $merchantMethods = $merchant->methods;

        foreach ($allow as $item)
        {
            if (isset($item[self::METHOD]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'method must be set for restriction'
                );
            }

            if (Method::isValid($item[self::METHOD]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid payment method given ',
                    'method');
            }
            if ($merchantMethods->isMethodEnabled($item[self::METHOD]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'method is not enabled for the merchant'
                );
            }

            $this->validateInput(studly_case($item[self::METHOD]).'_restriction', $item);
        }
    }
}
