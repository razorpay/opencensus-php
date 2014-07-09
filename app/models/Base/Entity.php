<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Entity extends \Eloquent
{
    /**
     * Indicates if the primary key is uuid.
     *
     * @var bool
     */
    public $uuid = false;

    protected static $sign = '';

    private static $delimiter = '-';

    protected $entity = '';

    /**
     * The input provided
     *
     * @var array
     */
    protected $input = array();

    /**
     * Fields which will be generated
     * during build
     * @var array
     */
    protected static $generators = array();

    /**
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = array();

    /**
     * Input keys which will be unset
     * before calling 'fill'
     */
    protected static $unsetCreateInput = array();

    public function build(array $input)
    {
        $this->input = $input;

        $this->modify($input);

        $this->validateInput($input, 'create');

        $this->generate($input);

        $this->unsetInput($input, 'create');

        $this->fill($input);

        return $this;
    }

    public function validateInput($input, $op)
    {
        $class = get_called_class();

        $pos1 = strpos($class, '\\');
        $pos2 = strrpos($class, '\\');

        $entity = substr($class, $pos1 + 1, $pos2 - $pos1 - 1);

        $validator = '\\Models\\'.$entity.'\\Validator';
        (new $validator)->validateInput($input, $op);
    }

    public function unsetInput(& $input, $operation)
    {
        foreach (static::${'unset'.ucfirst($operation).'Input'} as $key)
        {
            unset($input[$key]);
        }
    }

    public function toArrayPublic()
    {
        $array = $this->toArray();

        $array['id' ] = static::$sign . self::$delimiter . $array['id'];

        $array['entity'] = $this->entity;

        return $array;
    }

    protected function getDateFormat()
    {
        return 'U';
    }

    protected function asDateTime($value)
    {
        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and return as it is. Otherwise we will call the parent function to handle it.
        if (is_numeric($value))
        {
            return $value;
        }
        else
        {
            return parent::asDateTime($value);
        }
    }

    public function generate($input)
    {
        foreach (static::$generators as $field)
        {
            $this->generateAttribute($field, $input);
        }
    }

    public function modify(& $input)
    {
        foreach (static::$modifiers as $field)
        {
            $this->modifyAttribute($field, $input);
        }
    }

    public function modifyAttribute($attr, & $input)
    {
        $this->{'modify'.studly_case($attr)}($input);
    }

    public function generateAttribute($attr, $input)
    {
        $this->{'generate'.studly_case($attr)}($input);
    }

    public static function verifyIdAndStripSign(& $id)
    {
        self::stripSignOrFail($id);

        UniqueIdEntity::verifyUniqueId($id, true);
    }

    protected static function stripSignOrFail(& $id)
    {
        if (strpos($id, static::$sign . self::$delimiter) === false)
        {
            throw new Exception\BadRequestException(null, ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $len = strlen(static::$sign . self::$delimiter);

        $id = substr($id, $len);
    }
}
