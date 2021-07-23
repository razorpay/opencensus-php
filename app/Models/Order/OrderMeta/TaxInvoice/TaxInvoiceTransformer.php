<?php

namespace RZP\Models\Order\OrderMeta\TaxInvoice;

use Carbon\Carbon;
use RZP\Models\Payment\Method;
use RZP\Models\Order\OrderMeta\Type;
use RZP\Models\Order\OrderMeta\Validator;
use RZP\Models\Order\OrderMeta\BaseTransformer;
use RZP\Models\Order\OrderMeta\TaxInvoice\Fields;
use RZP\Models\Order\OrderMeta\TaxInvoice\Type as SupplyType;

/**
 * Class TaxInvoiceTransformer
 *
 * @package RZP\Models\Order\OrderMeta\TaxInvoice
 */
class TaxInvoiceTransformer extends BaseTransformer
{
    /**
     * @var string
     */
    protected $type = Type::TAX_INVOICE;
    /**
     * Methods for which `TaxInvoice` is applicable
     */
    protected $allowedMethods = [
        Method::UPI,
    ];

    /**
     * Implements Base Transformer Preprocess method.
     *
     * @return bool
     */
    public function preProcess(): bool
    {
        if (($this->isAllowedMethod() === false) or
            ($this->isGstFlow() === false))
        {
            return false;
        }

       return true;
    }

    /**
     * Implements BaseTransformer convertToOrderMetaValue
     * 1. Validates the value
     * 2. Sets default value
     * @return array
     */
    public function transform(): array
    {
        try
        {
            (new Validator())->validateInput('createTaxInvoice', $this->input);

            $this->setDefaults();

            return $this->input;
        }
        catch (Exception $ex)
        {
            $this->exception = $ex;

            throw $ex;
        }
    }

    /**
     * Function to check if the flow is GST. This depends if mandatory fields are set/unset.
     *
     * @return bool
     */
    public function isGstFlow(): bool
    {
        $nonGstFields = Fields::getMandatoryGstFields();

        $isGstFlow = collect($nonGstFields)
            ->filter(function($field) {
                return (isset($this->input[$field]) === false);
            })
            ->isEmpty();

        return $isGstFlow;
    }

    /**
     * Used to set default values : date and other GST derived fields
     */
    public function setDefaults(): void
    {
        $this->input[Fields::INVOICE_DATE] =
            $this->input[Fields::INVOICE_DATE] ?? Carbon::now()->getTimestamp();

        $this->setGstFields();
    }

    /**
     * If supplytype = interstate, then IGST=0 and CGST, SGST = GST/2
     * If supplytype = intrastate, then IGST=GST and CGST, SGST = 0
     * Please note :
     * Division can lead to decimal values. If the decimal part is >= '.5', ceil the value, else floor.
     * In our  case, divide by 2 will always lead to decimal value of 0.5, hence this logic ceils the value.
     * This has been confirmed by the internal finance team.
     */
    public function setGstFields() : void
    {
        $taxInvoice = $this->input;

        if (isset($taxInvoice[Fields::SUPPLY_TYPE]) === false)
        {
            return;
        }

        switch (strtolower($taxInvoice[Fields::SUPPLY_TYPE]))
        {
            case SupplyType::INTERSTATE:
                $taxInvoice[Fields::SGST_AMOUNT] = 0;
                $taxInvoice[Fields::CGST_AMOUNT] = 0;
                $taxInvoice[Fields::IGST_AMOUNT] = $taxInvoice[Fields::GST_AMOUNT];
                break;

            case SupplyType::INTRASTATE:
                $taxInvoice[Fields::SGST_AMOUNT] = (int) ceil($taxInvoice[Fields::GST_AMOUNT] / 2);
                $taxInvoice[Fields::CGST_AMOUNT] = (int) ceil($taxInvoice[Fields::GST_AMOUNT] / 2);
                $taxInvoice[Fields::IGST_AMOUNT] = 0;
                break;
        }

        $this->input = $taxInvoice;
    }

    /**
     * Function to check if the transformer is applicable for the method.
     *
     * @return bool
     */
    public function isAllowedMethod(): bool
    {
       return (in_array($this->order->getMethod(), $this->allowedMethods) === true);
    }
}

