<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Models\Order as Order;
use RZP\Exception\BaseException;

/**
 * Class BaseTransformer
 *
 * @package RZP\Models\Order\OrderMeta
 */
abstract class BaseTransformer
{
    /**
     * Stores the type for OrderMeta
     *
     * @var string
     *
     */
    protected $type;

    /**
     * Stores the value for OrderMeta
     *
     * @var array
     */
    protected $input;

    /**
     * @var $order Order
     */
    protected $order;

    /**
     * @var BaseException
     */
    protected $exception;

    /**
     * Function to pre-process before converting the value to order meta's value array
     *
     * @return bool
     */
    abstract public function preProcess(): bool;

    /**
     * Function to convert the $value to desired format of order meta value format.
     *
     * @return array
     */
    abstract public function transform(): array;

    /**
     * BaseTransformer constructor.
     *
     * @param Order\Entity $order
     * @param array        $input
     */
    public function __construct(Order\Entity $order, array $input)
    {
        $this->order = $order;

        $this->type = null;

        $this->input = $input;
    }

    protected function isSuccess(): bool
    {
        return is_null($this->exception);
    }
}

