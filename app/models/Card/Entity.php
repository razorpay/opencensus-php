<?php

namespace Models\Card;

use EE\Exception;
use Models\Card;
use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';

    const NAME = 'name';

    const EXPIRY_MONTH = 'expiry_month';

    const EXPIRY_YEAR = 'expiry_year';

    const LAST4 = 'last4';

    const NETWORK = 'network';

    const TYPE = 'type';

    const BANK = 'bank';

    const COUNTRY = 'country';

    const ADDRESS_LINE1 = 'address_line1';

    const ADDRESS_LINE2 = 'address_line2';

    const ADDRESS_CITY = 'address_city';

    const ADDRESS_STATE = 'address_state';

    const ADDRESS_ZIP = 'address_zip';

    const ADDRESS_COUNTRY = 'address_country';

    const COUNTRY_LENGTH = 2;

    protected $table = \Constants\Table::CARD;

    protected static $sign = 'card';

    protected $entity = 'card';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::LAST4,
        self::NETWORK,
        self::COUNTRY,
        self::TYPE,
        self::BANK,
        self::ADDRESS_LINE1,
        self::ADDRESS_LINE2,
        self::ADDRESS_STATE,
        self::ADDRESS_CITY,
        self::ADDRESS_ZIP,
        self::ADDRESS_COUNTRY,

        'cvv_check',
        'address_line1_check',
        'address_zip_check');

    protected $guarded = array(self::ID);

    protected static $modifiers = array('expiry_year', 'number');

    protected static $generators = array('last4', 'id');

    protected $visible = array(
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::LAST4,
        self::NETWORK);

    public function build(array $input = array())
    {
        try
        {
            parent::build($input);
        }
        catch (Exception\CardErrorException $e)
        {
            throw $e;
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new Exception\CardErrorException($e->getMessageBag(), 0, $e);
        }

        return $this;
    }

    public function generateLast4($input)
    {
        $last4 = substr($input['number'], -4);

        $this->setAttribute(self::LAST4, $last4);
    }

    public function modifyExpiryYear(& $input)
    {
        if ((isset($input['expiry_year'])) and
            (strlen($input['expiry_year']) == 2))
        {
            $input['expiry_year'] = '20'.$input['expiry_year'];
        }
    }

    public static function modifyNumber(& $input)
    {
        $number = $input['number'];

        if (is_string($number) === false)
        {
            return $number;
        }

        $number = str_replace(' ', '', $number);
        $number = str_replace('-', '', $number);

        $input['number'] = $number;
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function fillNetworkDetails($details, $iin)
    {
        $network = Card\Network::detectNetwork($iin);

        $this->setAttribute(self::NETWORK, $network);

        if ($details)
        {
            if ($network === Card\Network::UNIDENTIFIED)
            {
                if ($details['brand'] !== null)
                {
                    $network = strtolower($details['brand']);

                    if (Card\Network::checkNetworkValidity($network))

                    $this->setAttribute(self::NETWORK, $network);

                    // trace here
                }
                else
                {
                    // trace here
                }
            }

            $arr = array(
                'type' => $details['card_type'],
                'bank' => $details['bank'],
                'country' => $details['country_code']);

            $this->fill($arr);

            if ($network === null)
            {
                // @todo: trace
                return;
            }
        }
    }
}
