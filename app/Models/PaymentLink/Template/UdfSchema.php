<?php

namespace RZP\Models\PaymentLink\Template;

use JsonSchema;
use RZP\Exception\BadRequestValidationFailureException;

class UdfSchema
{
    public $schema;

    public $driver;

    /**
     * @var bool
     */
    protected $exists = false;

    public function __construct(string $id, string $name = null)
    {
        $path         = resource_path('jsonschema');
        $extension    = 'json';
        $this->driver = new FileAccess($path, $extension, $id, $name);

        $this->init();
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getSchema()
    {
        if ($this->driver->exists() === false)
        {
            return null;
        }

        $this->schema = $this->driver->get();

        return $this->schema;
    }

    public function getSchemaDecoded()
    {
        return json_decode($this->schema, true);
    }

    public function validate(array $input = [])
    {
        $data = (object) $input;

        $validator = new JsonSchema\Validator;
        $validator->validate($data, $this->getSchemaDecoded());

        if ($validator->isValid() === false)
        {
            $error    = head($validator->getErrors());
            $property = $error['property'];
            $message  = "The {$property} field is invalid. {$error['message']}";

            throw new BadRequestValidationFailureException($message);
        }
    }

    protected function init()
    {
        $schema = $this->getSchema();

        if ($schema !== null)
        {
            $this->setExists(true);
            $this->setSchema($schema);
        }
    }

    protected function setSchema(string $schema = null)
    {
        $this->schema = $schema;
    }

    protected function setExists(bool $exists)
    {
        $this->exists = $exists;
    }
}
