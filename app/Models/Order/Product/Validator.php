<?php


namespace RZP\Models\Order\Product;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use Illuminate\Support\Arr;
use RZP\Exception\ExtraFieldsException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const MUTUAL_FUND = 'mutual_fund';
    const LOAN        = 'loan';

    protected static $createRules = [
        Entity::ORDER_ID         => 'required|string|size:14',
        Entity::PRODUCT          => 'array',
        Entity::PRODUCT_TYPE     => 'required|string',
    ];


    protected static $createMutualFundProductRules = [
        Entity::TYPE                    => 'required|in:mutual_fund',
        Constants::RECEIPT              => 'sometimes|string',
        Constants::PLAN                 => 'sometimes|string',
        Constants::SCHEME               => 'sometimes|string',
        Constants::OPTION               => 'sometimes|string',
        Constants::AMOUNT               => 'sometimes|string',
        Constants::FOLIO                => 'sometimes|string',
        Constants::MF_MEMBER_ID         => 'sometimes|string',
        Constants::MF_USER_ID           => 'sometimes|string',
        Constants::MF_PARTNER           => 'sometimes|string',
        Constants::MF_INVESTMENT_TYPES  => 'sometimes|string',
        Constants::MF_AMC_CODE          => 'sometimes|string',
        Constants::NOTES                => 'sometimes|array',
    ];

    protected static $createLoanProductRules = [
        Entity::TYPE             => 'required|in:loan',
        Constants::LOAN_NUMBER   => 'sometimes|string',
        Constants::AMOUNT        => 'sometimes|string',
        Constants::RECEIPT       => 'sometimes|string',
    ];

    protected static $validProductTypes = [
        self::MUTUAL_FUND,
        self::LOAN,
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

    public function validateCreateMany($input)
    {
        if (Arr::isAssoc($input) === true)
        {
            $message = "'products' must be a list of 'product' objects and not an object";

            $field = Order\Entity::PRODUCTS;

            throw new BadRequestValidationFailureException($message, $field);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateOrderAmountWithProductTotal($orderAmount, $productsArray)
    {
        $productAmountSum = 0;

        foreach ($productsArray as $productArray)
        {
            if(array_key_exists(Constants::AMOUNT, $productArray) === true)
            {
                $productAmountSum += $productArray[Constants::AMOUNT];
            }
        }

        if ($productAmountSum !== $orderAmount)
        {
            $errorData = [
                'method' => 'pg_router'
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_AND_PRODUCTS_AMOUNT_MISMATCH,
                Order\Entity::PRODUCTS,
                $errorData
            );
        }
    }
}
