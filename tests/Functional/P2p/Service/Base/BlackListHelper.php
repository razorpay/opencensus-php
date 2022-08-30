<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Exception\RuntimeException;

class BlackListHelper extends P2pHelper
{
    public function create(array $content = [])
    {
        $this->validationJsonSchemaPath = 'blacklist/add_batch';

        $request = $this->request('blacklist/add_batch');

        $this->content($request, $content);

        return $this->post($request);
    }

    public function remove(array $content = [])
    {
        $this->validationJsonSchemaPath = 'blacklist/remove_batch';

        $request = $this->request('blacklist/remove_batch');

        $this->content($request, $content);

        return $this->post($request);
    }

    public function fetchAll()
    {
        $this->validationJsonSchemaPath = 'blacklist';

        $request = $this->request('blacklist');

        return $this->get($request);
    }
}
