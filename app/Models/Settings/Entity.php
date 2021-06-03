<?php

namespace RZP\Models\Settings;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\FundAccount;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    protected $table = Table::SETTINGS;

    protected $entity = 'settings';

    const KEY = 'key';

    const MODULE = 'module';

    const VALUE = 'value';

    public function getValue(string $key)
    {
        $this->getAttribute(self::KEY);
    }
}
