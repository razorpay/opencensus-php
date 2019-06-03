<?php

namespace RZP\Models\Tax;

use Lib\Gstin;
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
        return [
            Entity::GST_TAX_SLABS_V2  => Gst\Gst::TAX_SLABS_V2,
            Entity::GST_TAX_ID_MAP_V2 => Gst\GstTaxIdMap::get(),
        ];
    }

    public function getMetaStates(): array
    {
        $data = Gstin::getGstinStateMetadata();

        return (new Base\PublicCollection($data))->toArrayWithItems();
    }
}
