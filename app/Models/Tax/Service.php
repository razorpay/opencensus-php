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
        $taxIdMapV2 = Gst\GsttaxIdMap::get();

        // For backward compatibility, splits tax id map into 2 arrays for response
        foreach ($taxIdMapV2 as $k => $v)
        {
            if (starts_with($k, 'DEPRECATED_') === true)
            {
                $taxIdMap[str_after($k, 'DEPRECATED_')] = $v;
                unset($taxIdMapV2[$k]);
            }
        }

        return [
            Entity::GST_TAX_SLABS     => Gst\Gst::TAX_SLABS,
            Entity::GST_TAX_SLABS_V2  => Gst\Gst::TAX_SLABS_V2,
            Entity::GST_TAX_ID_MAP    => $taxIdMap,
            Entity::GST_TAX_ID_MAP_V2 => $taxIdMapV2,
        ];
    }

    public function getMetaStates(): array
    {
        $data = Gstin::getGstinStateMetadata();

        return (new Base\PublicCollection($data))->toArrayWithItems();
    }
}
