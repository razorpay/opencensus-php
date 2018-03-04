<?php

namespace RZP\Models\Tax;

use Lib\GSTIN;
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
            Entity::GST_TAX_SLABS  => Gst\Gst::TAX_SLABS,
            Entity::GST_TAX_ID_MAP => Gst\GstTaxIdMap::get(),
        ];
    }

    public function getMetaStates(): Base\PublicCollection
    {
        $data = ['state_tins' => GSTIN::getStatesToTinIdMap()];

        return Base\PublicCollection::make($data)->toArrayWithItems();
    }
}
