<?php

namespace RZP\Models\PaymentLink\Template;

class FileAccess
{
    const DEFAULT_FILENAME = 'default';

    protected $path;

    protected $extension;

    protected $id;

    protected $name;

    public function __construct(string $path, string $extension, string $id, string $name = null)
    {
        $this->path      = $path;
        $this->extension = $extension;
        $this->id        = $id;
        $this->name      = $name ?: self::DEFAULT_FILENAME;
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

    public function getFilePath(): string
    {
        return $this->path
               . DIRECTORY_SEPARATOR
               . $this->getFileName()
               . '.'
               . $this->extension;
    }

    public function getFileName(): string
    {
        return $this->id . '-' . $this->name;
    }
}
