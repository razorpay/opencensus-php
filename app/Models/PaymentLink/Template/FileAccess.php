<?php

namespace RZP\Models\PaymentLink\Template;

class FileAccess implements StorageAccess
{
    const DEFAULT_FILENAME = 'default';

    /**
     * File base path
     *
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $extension;

    /**
     * File identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Optional: file name version
     * @var string
     */
    protected $name;

    /**
     * FileAccess constructor.
     *
     * @param string      $path
     * @param string      $extension
     * @param string      $id
     * @param string|null $name
     */
    public function __construct(string $path, string $extension, string $id, string $name = null)
    {
        $this->path      = $path;
        $this->extension = $extension;
        $this->id        = $id;
        $this->name      = $name ?: self::DEFAULT_FILENAME;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(): bool
    {
        return (file_exists($this->getFilePath()) === true);
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        if ($this->exists() === false)
        {
            return null;
        }

        // On failure file_get_contents() returns false.
        return file_get_contents($this->getFilePath()) ?: null;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->path
               . DIRECTORY_SEPARATOR
               . $this->getFileName()
               . '.'
               . $this->extension;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->id . '-' . $this->name;
    }
}
