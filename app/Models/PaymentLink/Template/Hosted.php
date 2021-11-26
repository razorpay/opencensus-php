<?php

namespace RZP\Models\PaymentLink\Template;

class Hosted
{
    const BASE_PATH = 'views/hostedpage';

    /**
     * @var FileAccess
     */
    public $driver;

    public function __construct(string $id, string $name = null)
    {
        $path      = resource_path(self::BASE_PATH);
        $extension = 'blade.php';

        $this->driver = new FileAccess($path, $extension, $id, $name);
    }

    public function exists(): bool
    {
        return $this->driver->exists();
    }

    public function getViewName()
    {
        return $this->driver->getFileName();
    }
}
