<?php

namespace RZP\Models\PaymentLink\Template;

class Hosted
{
    public $driver;

    public function __construct(string $id, string $name = null)
    {
        $path         = resource_path('views/hostedpage');
        $extension    = 'blade.php';
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
