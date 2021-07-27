<?php


namespace RZP\Models\Merchant\Detail\Upload;

use App;

use RZP\Base;
use RZP\Exception;


class Validator extends Base\Validator
{

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();
    }

    protected static $uploadMerchantRules = [
        Constants::FORMAT   => 'required|string|max:255',
        Constants::FILE     => 'required|file',
    ];
}
