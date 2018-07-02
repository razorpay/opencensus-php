<?php

namespace RZP\Models\PaymentLink\Template;

use JsonSchema;
use JsonSchema\Constraints\Constraint as JsonSchemaConstraint;

use RZP\Exception\BadRequestValidationFailureException;

class UdfSchema
{
    /**
     * @var string|null
     */
    public $schema;

    /**
     * @var FileAccess
     */
    public $driver;

    /**
     * @var bool
     */
    protected $exists = false;

    public function __construct(string $id, string $name = null)
    {
        $path      = resource_path('jsonschema');
        $extension = 'json';

        // Initiate the file access driver
        $this->driver = new FileAccess($path, $extension, $id, $name);

        $this->init();
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
     * Return the JSON schema as an array
     * Null, on error
     *
     * @return mixed
     */
    public function getSchemaDecoded()
    {
        return json_decode($this->schema, true);
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
            $this->getSchemaDecoded(),
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
     * Initialize UDF schema properties
     */
    protected function init()
    {
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
