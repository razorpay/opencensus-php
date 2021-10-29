<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Services\DiagClient;
use RZP\Services\SalesForceClient;
use RZP\Models\Merchant\Attribute\Validator;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $core;

    /** @var $diag DiagClient */
    protected $diag;

    /** @var $salesforce SalesForceClient */
    protected $salesforce;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->merchant_attribute;

        $this->diag = $this->app['diag'];

        $this->salesforce = $this->app['salesforce'];

    }

    /**
     * Group & Types - follows flat structure
     * e.g. {"type":"business_category","value": "School"},
     *   {"type":"monthly_payout_count","value": "1000"}
     * Validates Group & Types - Refer to entity to find allowed values map
     * Create or Update Group & Types at Merchant Product Level.
     * At Product, Group & Type level matching happens to identify update entries.
     * @param string $group
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function upsert(string $group, array $input, string $saveProduct = null)
    {
        $merchant = $this->merchant;
        $product =  $saveProduct ?? $this->auth->getRequestOriginProduct();
        $attributeInputValidator = new Validator();

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'merchant' => $merchant ,
            'received_input' => $input,
            'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
        ]);

        if(empty($merchant) === true and
           $this->app['basicauth']->isAdminAuth() === true)
        {
            $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
                'coming_inside_admin_auth' => true,
                'merchant' => $merchant ,
                'received_input' => $input,
                'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
            ]);
            $attributeInputValidator->validateInput('admin_upsert', $input);
            $merchant = $this->repo->merchant->findOrFail($input[Common::MERCHANT_ID]);
            $product = $input['product'] ?? Product::BANKING;
            $input = array_pull($input, Validator::PREFERENCES);
        }

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'input_after_admin_auth_thing' => $input ,
            'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
        ]);

        foreach ($input as $item){
            $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
                'item_for_validation' => $item,
                'complete_input' => $input,
                'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
            ]);
            //Validate input for group & type
            $attributeInputValidator->validateInput('upsert_input_validation', $item);
            $item[Entity::GROUP] = $group;
            $attributeInputValidator->validateGroupAndType($item);
        }

        $types = array_column($input, 'type');

        $merchantAttributes =  $this->core->fetchKeyValues($merchant, $product, $group, $types);

        //find existing keys
        $merchantAttributesByKeys = $merchantAttributes->getDictionaryByAttribute(Entity::TYPE);
        $existingKeys = array_keys($merchantAttributesByKeys);

        // Do we need transaction? We don't need it. All entries can be saved independently
        foreach ($input as $item){
            $item[Entity::PRODUCT] = $product;
            $item[Entity::GROUP] = $group;

            if (in_array($item['type'], $existingKeys)){
                $this->core->update($merchantAttributesByKeys[$item['type']] , $item);
            } else {
                $this->core->create($item, $merchant);
            }
        }

        return $this->core->fetchKeyValues($merchant, $product, $group, $types);
    }

    public function upsertPreferencesNitroHack(array $input){
        $merchant = $this->merchant;
        $product = Product::BANKING;

        $createdInput = array([
            'type' => Type::CA_ALLOCATED_BANK,
            'value' => $input['allocated_bank']
                              ], [
            'type' => Type::CA_ONBOARDING_FLOW,
            'value' => $input['onboarding_flow']
        ], [
            'type' => Type::CA_CAMPAIGN_ID,
            'value' => $input['campaign_id']
        ]);


        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'inside_upsert_nitro_hack' => 'true',
            'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
            'merchant' => $merchant ,
            'request_origin' => $this->auth->getRequestOriginProduct(),
            'received_input' => $input,
            'created_input' => $createdInput,
        ]);

        return $this->upsert(Group::X_MERCHANT_CURRENT_ACCOUNTS, $createdInput, $product);
    }

    /**
     * Get merchant preferences by group and type
     * @param string $group
     * @param string|null $type
     * @return mixed
     */
    public function getPreferencesByGroupAndType(string $group, string $type = null)
    {
        $merchant = $this->merchant;

        $product = $this->auth->getRequestOriginProduct();

        $type = !empty($type) ? [$type] : [];

        $column = null;

        if (in_array($group, [Group::X_MERCHANT_SOURCE, Group::X_MERCHANT_INTENT], true) === true)
        {
            $column = Entity::CREATED_AT;
        }

        return $this->core->fetchKeyValues($merchant, $product, $group, $type, $column);
    }
}
