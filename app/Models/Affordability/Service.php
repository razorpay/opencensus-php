<?php

namespace RZP\Models\Affordability;

use RZP\Models\Base;
use RZP\Models\Emi\Service as EmiService;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Methods\Core as MethodsCore;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Models\Merchant\Checkout;
use RZP\Models\Offer\Core as OfferCore;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\PayLater;

class Service extends Base\Service
{
    /** @var Validator */
    private $validator;

    public function __construct(Validator $validator)
    {
        parent::__construct();

        $this->validator = $validator;
    }

    public function fetchSuite(array $input): array
    {
        $this->validator->validateInput('fetch', $input);

        $this->findAndSetMerchantByKey($input['key']);

        $data = [];

        if ($this->isEnabled($data) === false) {
            return $data;
        }

        foreach($input['components'] as $component)
        {
            $functionName = 'fetch' . ucfirst(camel_case($component)) . 'Component';

            if (method_exists($this, $functionName))
            {
                $this->{$functionName}($data);
            }
        }

        return $data;
    }

    /**
     * Check if affordability widget feature is enabled for the merchant.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function isEnabled(array &$data): bool
    {
        $data['enabled'] = $this->merchant->isFeatureEnabled(Features::AFFORDABILITY_WIDGET);

        return $data['enabled'];
    }

    protected function fetchCardlessEmiComponent(array &$data): void
    {
        $providers = [];

        if ($this->merchant->methods->isCardlessEmiEnabled() === true)
        {
            $providers = (new MethodsCore())->getProviders($this->merchant, PaymentMethod::CARDLESS_EMI);

            $providers = $this->formatProviders($providers, CardlessEmi::MIN_AMOUNTS);
        }

        $data['entities']['cardless_emi']['providers'] = $providers;
    }

    protected function fetchEmiComponent(array &$data): void
    {
        $items = [];

        if ($this->merchant->methods->isEmiEnabled() === true)
        {
            $items = (new EmiService())->getEmiPlansAndOptions()['options'];
        }

        $data['entities']['emi']['items'] = $items;
    }

    protected function fetchOffersComponent(array &$data): void
    {
        $data['entities']['offers']['items'] = (new OfferCore())->fetchOffersForAffordability($this->merchant->getId());
    }

    protected function fetchPaylaterComponent(array &$data): void
    {
        $providers = [];

        if ($this->merchant->methods->isPayLaterEnabled() === true)
        {
            $providers = (new MethodsCore())->getProviders($this->merchant, PaymentMethod::PAYLATER);

            $providers = $this->formatProviders($providers, PayLater::MIN_AMOUNTS);
        }

        $data['entities']['paylater']['providers'] = $providers;
    }

    protected function fetchOptionsComponent(array &$data): void
    {
        $data['options']['theme']['color'] = $this->merchant->getBrandColor();

        $data['options']['image'] = $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE);
    }

    /**
     * @param string $keyId Public Key of the Merchant
     */
    private function findAndSetMerchantByKey(string $keyId): void
    {
        $key = $this->repo->key->findByPublicId($keyId);

        $this->merchant = $key->merchant;

        // Base/Service fetches merchant from auth. Removing this gives us null value error on $this->merchant.
        $this->auth->setMerchant($this->merchant);
    }

    /**
     * Format CardlessEmi & PayLater providers response.
     *
     * @param array $providers  CardlessEmi (or) PayLater providers
     * @param array $minAmounts Minimum order/transaction amount for each provider
     *
     * @return array
     */
    protected function formatProviders(array $providers, array $minAmounts): array
    {
        $response = [];

        foreach ($providers as $provider => $enabled) {
            $response[$provider] = [
                'enabled' => $enabled,
                'min_amount' => $minAmounts[$provider] ?? null,
            ];
        }

        return $response;
    }
}
