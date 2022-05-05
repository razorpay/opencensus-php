<?php

namespace RZP\Models\PaymentLink\Template;

use JsonSchema;
use JsonSchema\Constraints\Constraint as JsonSchemaConstraint;

use RZP\Models\Base\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class UdfSchema
{
    /**
     * We do not store regular expression in settings in db. A keyword is stored
     * which is translated to regular expression per below map for validations.
     * FE also has the same mapping for rendering view forms.
     */
    const PATTERN_NAME_TO_REGEX_MAP = [
        Pattern::EMAIL          => '^(?i)(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$',
        Pattern::NUMBER         => '^[+-]?([0-9]*[.])?[0-9]+$',
        Pattern::ALPHABETS      => '^(?i)([a-z]+ ?)*$',
        Pattern::ALPHANUMERIC   => '^(?i)[a-z0-9]+$',
        Pattern::PHONE          => '^([0-9]){8,}$',
        Pattern::AMOUNT         => '^[0-9]+(.([0-9]){1,2})?$',
        Pattern::URL            => '^(?i)(?:(?:http|https|ftp):\/\/)?(?:\S+(?::\S*)?@)?(?:(?:(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[0-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z0-9]+-?)*[a-z0-9]+)(?:\.(?:[a-z0-9]+-?)*[a-z0-9]+)*(?:\.(?:[a-z]{2,})))|localhost)(?::\d{2,5})?(?:(\/|\?|#)[^\s]*)?$',
        Pattern::PAN            => '^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$',
        Pattern::DATE           => '^(([0]?[1-9])?|([1-2][0-9])?|([3][0,1])?) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)(, | )(1[6-8][0-9]{2}|19[0-8][0-9]|199[0-9]|[2-9][0-9]{3})$',
    ];

    /**
     * @var string|null
     */
    public $schema;

    /**
     * @var StorageAccess
     */
    public $driver;

    /**
     * @var bool
     */
    protected $exists = false;

    /**
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->init($entity);
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * We keep schema in a variant format of RFC, except for the ones created in the beginning.
     * Sample variant JSON schema: https://github.com/razorpay/dashboard/blob/f0fe97a7c96ce4501a958a73cbc2065d3bd01e56/web/js/merchant/containers/PaymentPages/Pages/V2/form_schema.js
     * Sample standard JSON schema: http://json-schema.org/learn/miscellaneous-examples.html
     *
     * For validation purposes(existing libraries against RFC schema), this method does the conversion and returns the valid schema as array.
     * @return array
     */
    public function getSchemaInRfcFormatForValidation(): array
    {
        $schema = json_decode($this->schema, true);

        // For backward compatibility.
        if (is_sequential_array($schema) === false)
        {
            return $schema;
        }

        // Converts our variant JSON schema to standard JSON schema for validation usage.
        $formatted = [
            'title'      => '',
            'type'       => 'object',
            'required'   => [],
            'properties' => [],
        ];

        foreach ($schema as $v)
        {
            $property = array_pull($v, 'name');
            $required = array_pull($v, 'required');

            if ($required === true)
            {
                $formatted['required'][] = $property;
            }

            // Remap pattern name to regular expression if exists
            if ((isset($v['pattern']) === true) and
                ((isset(self::PATTERN_NAME_TO_REGEX_MAP[$v['pattern']]) === true)))
            {
                $v['pattern'] = self::PATTERN_NAME_TO_REGEX_MAP[$v['pattern']];
            }

            $formatted['properties'][$property] = $v;
        }

        return $formatted;
    }

    /**
     * Validate the input array sent against the JSON
     * schema set in $this->schema
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validate(array $input = [])
    {
        $data = (object) $input;

        $validator = new JsonSchema\Validator;

        //
        // The `CHECK_MODE_COERCE_TYPES` option will convert the input data type to the
        // required one, whenever possible. This is used because our input values for
        // notes are always in string format
        //
        $validator->validate(
            $data,
            $this->getSchemaInRfcFormatForValidation(),
            JsonSchemaConstraint::CHECK_MODE_COERCE_TYPES);

        if ($validator->isValid() === false)
        {
            $error    = head($validator->getErrors());
            $property = $error['property'];
            $message  = "The {$property} field is invalid. {$error['message']}";

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Initialize UDF storage driver and underlying schema properties.
     * @param Entity $entity
     */
    protected function init(Entity $entity)
    {
        // Initializes correct storage driver.
        // For backward compatibility.
        $jsonSchemaId = $entity->getUdfJsonschemaId();
        if ($jsonSchemaId !== null)
        {
            $this->driver = new FileAccess(resource_path('jsonschema'), 'json', $jsonSchemaId);
        }
        // Now we use settings.
        else
        {
            $this->driver = new SettingsAccess($entity);
        }

        // Loads schema and sets properties.
        $this->schema = $this->loadSchema();
        $this->exists = ($this->schema !== null);
    }

    protected function loadSchema()
    {
        if ($this->driver->exists() === false)
        {
            return null;
        }

        return $this->driver->get();
    }
}
