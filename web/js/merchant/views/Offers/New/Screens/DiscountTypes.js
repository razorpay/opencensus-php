import React, { useMemo } from 'react';
import Input from 'common/new-ui/Input';
import {
  DISCOUNT_TYPES,
  OFFER_TYPES,
  MAX_DISCOUNT,
  REDEMPTION_TYPE_OPTIONS,
} from 'merchant/views/Offers/constants';
import { rupeesToPaise } from 'common/utils/rzp-utils';

const DISCOUNT_TYPES_OPTIONS = [
  { label: '--Select Type--', name: '' },
  { label: 'Flat', name: DISCOUNT_TYPES.FLAT },
  { label: 'Percentage', name: DISCOUNT_TYPES.PERCENT },
];

export default function DiscountType({
  offerType,
  formData,
  currencySymbol,
  isFormLocked,
  hideDiscountType,
  showSubscriptionOfferFields,
  emiData = {},
}) {
  const isFLATDiscount = formData.discount_type === DISCOUNT_TYPES.FLAT;
  const isPERCENTDiscount = formData.discount_type === DISCOUNT_TYPES.PERCENT;
  const isNO_COST_EMIDiscount = formData.discount_type === DISCOUNT_TYPES.NO_COST_EMI;
  const isInstantOffer = offerType === OFFER_TYPES.Instant;

  const minAmount = useMemo(() => {
    const _minAmount = Object.keys(emiData?.emi_plans || {}).reduce((min, current) => {
      if (
        emiData.emi_plans[current] &&
        emiData.emi_plans[current].min_amount &&
        emiData.emi_plans[current].min_amount < min
      ) {
        min = emiData.emi_plans[current].min_amount;
      }
      return min;
    }, Infinity);
    return _minAmount === Infinity ? 0 : _minAmount;
  }, [emiData]);
  const showNoOfCycles = formData.redemption_type === 'cycle';

  return (
    <React.Fragment>
      {isInstantOffer && (
        <>
          <strong>Instant Discount</strong>
          <p>The customer will pay the discounted price for the product</p>
        </>
      )}

      {showSubscriptionOfferFields && (
        <>
          <Input.Select
            required
            name="redemption_type"
            label="Redemption Type"
            options={REDEMPTION_TYPE_OPTIONS}
            defaultValue={formData.redemption_type}
            disabled={isFormLocked}
            description="In how many subscription cycles this offer will be applied."
          />

          {showNoOfCycles && (
            <Input
              required
              type="number"
              name="no_of_cycles"
              placeholder="E.g. 3"
              defaultValue={formData.no_of_cycles}
              disabled={isFormLocked}
              description="Number of cycles in which offer will be applied."
            />
          )}
        </>
      )}

      <div>
        {!hideDiscountType && (
          <Input.Select
            required
            name="discount_type"
            class="Input--half"
            label="Discount Type"
            placeholder="Discount Type"
            defaultValue={formData.discount_type}
            options={DISCOUNT_TYPES_OPTIONS}
            validator={validateDiscountType}
            disabled={isFormLocked}
          />
        )}

        {formData.discount_type && (
          <Input
            required={!isPERCENTDiscount}
            name="min_amount"
            placeholder="0.00"
            label="Minimum Order amount"
            defaultValue={formData.min_amount}
            class="Input--half"
            validator={validateMinAmount({
              flat_cashback: formData.flat_cashback,
              isPERCENTDiscount,
              max_amount: formData.max_amount,
              minAmount,
            })}
            addonBefore={currencySymbol}
            disabled={isFormLocked}
          />
        )}

        {isNO_COST_EMIDiscount && (
          <Input
            name="max_order_amount"
            label="Maximum Order amount"
            placeholder="0.00"
            defaultValue={formData.max_order_amount}
            class="Input--half"
            addonBefore={currencySymbol}
            validator={validateMaxOrderAmount(formData.min_amount)}
          />
        )}

        {isFLATDiscount && (
          <Input
            required
            name="flat_cashback"
            label="Discount Worth"
            placeholder="0.00"
            description="Discount worth in cash"
            class="Input--half"
            defaultValue={formData.flat_cashback}
            validator={validateFlatCashback(formData.min_amount)}
            addonBefore={currencySymbol}
            disabled={isFormLocked}
          />
        )}

        {isPERCENTDiscount && (
          <React.Fragment>
            <Input
              required
              name="percent_rate"
              label="Discount Worth"
              class="Input--half"
              placeholder="0.00"
              description="Discount worth in Percent"
              addonAfter={<span>%</span>}
              defaultValue={formData.percent_rate}
              validator={validatePercentRate}
              disabled={isFormLocked}
            />

            <Input
              required
              label={`Maximum ${isInstantOffer ? 'Discount' : 'Cashback'}`}
              placeholder="0.00"
              name="max_cashback"
              defaultValue={formData.max_cashback}
              class="Input--half"
              description={`Maximum ${isInstantOffer ? 'discount' : 'cashback'} for this offer`}
              addonBefore={currencySymbol}
              validator={validateMaxCashback}
              disabled={isFormLocked}
            />
          </React.Fragment>
        )}
      </div>
    </React.Fragment>
  );
}

function validateDiscountType(val) {
  if (!val || val == '') {
    return 'Please select a discount type';
  }
  return false;
}

function validatePercentRate(val) {
  if (!val) {
    return 'Should be valid number between 0 and 100';
  }

  val = parseFloat(val);
  if (val > 99.99 || val < 0.01) {
    return 'Percentage should be between 0 and 100';
  }

  const decimalPointError = validateDecimalPointValue(val);
  if (decimalPointError) return decimalPointError;
  return false;
}

function validateFlatCashback(min_amount) {
  return (val) => {
    const decimalPointError = validateDecimalPointValue(val);
    if (decimalPointError) return decimalPointError;

    val = parseFloat(val);
    // Converting to value entered in RS to Paise for proper validation
    val = rupeesToPaise(val);
    if (val > MAX_DISCOUNT) {
      return `Maximum value allowed is ${MAX_DISCOUNT}`;
    }
    const minAmount = rupeesToPaise(min_amount);

    if (val > minAmount) {
      return 'Discount value cannot be greater than minimum amount';
    }
    return false;
  };
}

function validateMinAmount({ flat_cashback, isPERCENTDiscount, max_order_amount, minAmount }) {
  return (val) => {
    if (!val && isPERCENTDiscount) {
      return false;
    }

    const decimalPointError = validateDecimalPointValue(val);
    if (decimalPointError) return decimalPointError;

    val = parseFloat(val);
    // Converting to value entered in RS to Paise for proper validation
    val = rupeesToPaise(val);
    if (val > MAX_DISCOUNT) {
      return `Maximum value allowed is ${MAX_DISCOUNT}`;
    }
    const flatCashback = rupeesToPaise(flat_cashback);
    if (val < flatCashback) {
      return 'Minimum payment is less than discount value';
    }
    const maxOrderAmount = rupeesToPaise(max_order_amount);
    if (maxOrderAmount && maxOrderAmount < val) {
      return 'Minimum order amount should be less than max order amount';
    }
    if (minAmount && val < minAmount) {
      return `Minimum order amount should be greater than or equal to ₹${minAmount / 100}`;
    }
    return false;
  };
}

function validateMaxCashback(val) {
  const decimalPointError = validateDecimalPointValue(val);
  if (decimalPointError) return decimalPointError;

  val = parseFloat(val);
  // Converting to value entered in RS to Paise for proper validation
  val = rupeesToPaise(val);
  if (val > MAX_DISCOUNT) {
    return `Maximum value allowed is ${MAX_DISCOUNT}`;
  }
  return false;
}

function validateMaxOrderAmount(min_amount) {
  return (val) => {
    if (!val || val === '') return false;

    const decimalPointError = validateDecimalPointValue(val);
    if (decimalPointError) return decimalPointError;

    val = parseFloat(val);
    // Converting to value entered in RS to Paise for proper validation
    val = rupeesToPaise(val);
    if (val > MAX_DISCOUNT) {
      return `Maximum value allowed is ${MAX_DISCOUNT}`;
    }
    const minAmount = rupeesToPaise(min_amount);
    if (!minAmount || val < minAmount) {
      return `Maximum order amount should be more than minimum order amount`;
    }
    return false;
  };
}

const DECIMAL_POINT_REGEX = '^[0-9]+(.[0-9][0-9]?)?$';
function validateDecimalPointValue(val) {
  const isValid = new RegExp(DECIMAL_POINT_REGEX).test(val);

  if (!isValid) return 'Please enter number upto 2 decimal points';
  return false;
}
