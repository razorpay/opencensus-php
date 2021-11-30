<?php

namespace RZP\Models\Affordability;

use RZP\Models\Base;

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

        if (!$this->isEnabled($data)) {
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
        $data['enabled'] = true;

        return $data['enabled'];
    }

    protected function fetchCardlessEmiComponent(array &$data): void
    {
        $data['entities']['cardless_emi']['items'] = [];
        $data['entities']['cardless_emi']['providers'] = [];
    }

    protected function fetchEmiComponent(array &$data): void
    {
        $data['entities']['emi']['items'] = [];
    }

    protected function fetchOffersComponent(array &$data): void
    {
        $data['entities']['offers']['items'] = [];
    }

    protected function fetchPaylaterComponent(array &$data): void
    {
        $data['entities']['paylater']['providers'] = [];
    }

    /**
     * @param string $keyId Public Key of the Merchant
     */
    private function findAndSetMerchantByKey(string $keyId): void
    {
        $key = $this->repo->key->findByPublicId($keyId);

        $this->merchant = $key->merchant;
    }
}
