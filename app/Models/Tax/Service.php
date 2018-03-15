<?php

namespace RZP\Models\Tax;

use RZP\Models\Base;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->tax;
    }

    public function getMetaGstTaxes(): array
    {
        $data = [
            'slabs'   => Gst::getIndiaTaxSlabs(),
            'tax_ids' => Gst::getIndiaTaxIds(),
        ];

        return $data;
    }
}
