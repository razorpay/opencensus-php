<?php


namespace RZP\Models\Merchant\PaymentLimit;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use Validator as LaravelValidator;


class Validator extends Base\Validator
{
    protected static $csvHeaderRules = [
        0 => 'required|string|in:merchant_id',
        1 => 'required|string|in:max_payment_amount',
        2 => 'required|string|in:max_international_payment_amount',
    ];

    public $csvHeaderActionCustomAttributes = [
        0 => 'first',
        1 => 'second',
        2 => 'third',
    ];

    const NUMBER_OF_ROWS_ALLOWED = 1000;
    const ROW_ENTITY = 'row';
    const ALLOWED_LIMIT = [
        self::ROW_ENTITY => self::NUMBER_OF_ROWS_ALLOWED,
    ];

    protected function validateInputValues($operation, $input)
    {
        $messages = [
            'required' => 'Column :attribute name is required',
            'in' => 'Column :attribute name should be :values',
        ];

        $rulesVar = $this->getRulesVariableName($operation);

        $customAttributes = $this->getCustomAttributes($operation);

        $validator = LaravelValidator::make(
            $input,
            static::$$rulesVar,
            $messages,
            $customAttributes);

        $this->laravelValidatorInstance = $validator;

        $validator->setEntityValidator($this);

        if ($validator->fails()) {
            $this->processValidationFailure($validator->messages(), $operation, $input);
        }

        return $this;
    }

    protected function assertFileEntityLimit($entries, $entityType)
    {
        $countInputEntries = count($entries);
        $limit = self::ALLOWED_LIMIT[$entityType];

        if ($countInputEntries > $limit) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT,
                null,
                [
                    'type' => 'payment_limit_' . $entityType,
                    'total' => $countInputEntries,
                ]);
        }
    }

    public function validateRowsLimit(array $entries)
    {
        $this->assertFileEntityLimit($entries, self::ROW_ENTITY);
    }

}
