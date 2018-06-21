<?php

namespace RZP\Models\PaymentLink\Template;

class FileAccess
{
    const UDF_SCHEMA  = 'udf_schema';
    const HOSTED_PAGE = 'hosted_page';

    const DEFAULT_FILENAME = 'default';

    protected static $extension = [
        self::UDF_SCHEMA  => 'json',
        self::HOSTED_PAGE => 'blade.php',
    ];

    protected $id;

    protected $type;

    protected $name;

    protected $storagePath;

    public function __construct(string $type, string $id, string $name = null)
    {
        $this->id   = $id;
        $this->type = $type;
        $this->name = $name ?: self::DEFAULT_FILENAME;
    }

    public function exists(): bool
    {
        return (file_exists($this->getFilePath()) === true);
    }

    public function get()
    {
        if ($this->exists() === false)
        {
            return null;
        }

        return file_get_contents($this->getFilePath());
    }

    public function getFileBasePath()
    {
        $key = '';
        switch ($this->type)
        {
            case self::UDF_SCHEMA:
                $key ='jsonschema';
                break;

            case self::HOSTED_PAGE:
                $key = 'views/hostedpage';
                break;
        }

        return resource_path($key);
    }

    public function getFilePath(): string
    {
        $base =  $this->getFileBasePath();

        return $base
               . DIRECTORY_SEPARATOR
               . $this->getViewName() . '.'
               . self::$extension[$this->type];
    }

    public function getViewName(): string
    {
        return $this->id . '-' . $this->name;
    }
}
