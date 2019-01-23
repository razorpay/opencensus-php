<?php

namespace RZP\Models\P2p\Base\Libraries;

use Illuminate\Support;

class ArrayBag extends Support\Collection
{
    public function putMany(array $items): self
    {
        foreach ($items as $key => $value)
        {
            $this->put($key, $value);
        }

        return $this;
    }
}
