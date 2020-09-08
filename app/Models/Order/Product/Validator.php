<?php


namespace RZP\Models\Order\Product;

use RZP\Base;
use RZP\Exception\ExtraFieldsException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Order\ProductType;

class Validator extends Base\Validator
{
    const MUTUAL_FUND = 'mutual_fund';

    protected static $createRules = [
        Entity::ORDER_ID         => 'required|string|size:14',
        Entity::PRODUCT          => 'required|array',
        Entity::PRODUCT_TYPE     => 'required|string',
    ];


    protected static $createMutualFundProductRules = [
        Entity::TYPE         => 'required|in:mutual_fund',
        Constants::RECEIPT   => 'sometimes|string',
        Constants::PLAN      => 'sometimes|string',
        Constants::SCHEME    => 'sometimes|string',
        Constants::OPTION    => 'sometimes|string',
        Constants::AMOUNT    => 'sometimes|string',
        Constants::FOLIO     => 'sometimes|string',
        Constants::NOTES     => 'sometimes|array',
    ];

    protected static $validProductTypes = [
        self::MUTUAL_FUND,
    ];

    public function validateCreateProduct(array $input)
    {
       if (isset($input[Entity::TYPE]) === false)
       {
           $message = 'The type field is required for Product';

           throw new BadRequestValidationFailureException($message, Entity::TYPE);
       }

       if (in_array($input[Entity::TYPE], self::$validProductTypes) === false)
       {
           $message = $input[Entity::TYPE] . ' is not a valid product type';

           throw new BadRequestValidationFailureException($message, Entity::TYPE);
       }

       $operation = 'create_' . $input[Entity::TYPE] . '_product';

        try
        {
            $this->validateInput($operation, $input);
        }
        catch (ExtraFieldsException $exception)
        {
            // catching and rethrowing to make it explicit to the merchant where the extra field is present and for what product type
            $message = $exception->getMessage() . ' for product of type ' . $input[Entity::TYPE];

            $field = $exception->getExtraFields();

            throw new BadRequestValidationFailureException($message, $field);
        }
    }
}
