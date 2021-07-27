<?php
namespace RZP\Services\Bbps\Impl;

use RZP\Models\Merchant;

class Mock
{
    protected $bbpsConfig;

    protected $trace;

    public function __construct($bbpsConfig, $trace)
    {
        $this->bbpsConfig = $bbpsConfig;

        $this->trace = $trace;
    }

    public function getIframeForDashboard(Merchant\Entity $merchant, string $mode)
    {
        return['iframe_embed_url' => 'https://www.wikipedia.org'];
    }
}
