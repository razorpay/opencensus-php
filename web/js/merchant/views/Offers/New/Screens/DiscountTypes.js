import React, { useMemo } from 'react';
import {
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Text,
  RupeeIcon,
  PercentIcon,
} from '@razorpay/blade/components';

import { rupeesToPaise } from 'common/utils/rzp-utils';
import {
  DISCOUNT_TYPES,
  OFFER_TYPES,
  MAX_DISCOUNT,
  REDEMPTION_TYPE_OPTIONS,
} from 'merchant/views/Offers/constants';
const DISCOUNT_TYPES_OPTIONS = [
  { label: '--Select Type--', name: '' },
  { label: 'Flat', name: DISCOUNT_TYPES.FLAT },
  { label: 'Percentage', name: DISCOUNT_TYPES.PERCENT },
];

export default function DiscountType({
  offerType,
  isFormLocked,
  hideDiscountType,
  showSubscriptionOfferFields,
  emiData = {},
  values,
  setFieldTouched,
  setFieldValue,
  errors,
  touched,
}) {
  const isFlatDiscount = values.discount_type === DISCOUNT_TYPES.FLAT;
  const isPercentDiscount = values.discount_type === DISCOUNT_TYPES.PERCENT;
  const isNo_Cost_EmiDiscount = values.discount_type === DISCOUNT_TYPES.NO_COST_EMI;
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

  const showNoOfCycles = values.redemption_type === 'cycle';

  const handleFormChange = (name, value) => {
    setFieldTouched(name);
    setFieldValue(name, value);
  };

  errors.discount_type = validateDiscountType(values.discount_type);
  isPercentDiscount
    ? (errors.percent_rate = validatePercentRate(values.percent_rate))
    : Object.fromEntries(Object.entries(errors).filter(([key]) => key !== 'percent_rate'));
  isFlatDiscount
    ? (errors.flat_cashback = validateFlatCashback(values.flat_cashback, values.min_amount))
    : Object.fromEntries(Object.entries(errors).filter(([key]) => key !== 'flat_cashback'));

  errors.min_amount = validateMinAmount({
    val: values.min_amount,
    flat_cashback: values.flat_cashback,
    isPercentDiscount,
    max_order_amount: values.max_order_amount,
    minAmount,
  });
  isPercentDiscount
    ? (errors.max_cashback = validateMaxCashback(values.max_cashback))
    : Object.fromEntries(Object.entries(errors).filter(([key]) => key !== 'max_cashback'));
  showNoOfCycles
    ? (errors.no_of_cycles = validateNoOfCycles(values.no_of_cycles))
    : Object.fromEntries(Object.entries(errors).filter(([key]) => key !== 'no_of_cycles'));
  errors.max_order_amount = validateMaxOrderAmount(values.max_order_amount, values.min_amount);

  return (
    <React.Fragment>
      {isInstantOffer && (
        <>
          <Text weight="semibold" color="surface.text.gray.staticBlack.Normal">
            Instant Discount
          </Text>
          <Text color="surface.text.gray.staticBlack.Normal" marginBottom="spacing.7">
            The customer will pay the discounted price for the product
          </Text>
        </>
      )}

      {showSubscriptionOfferFields && (
        <>
          <Dropdown isDisabled={isFormLocked} marginTop="spacing.7" marginBottom="spacing.7">
            <SelectInput
              isRequired
              necessityIndicator="required"
              label="Redemption Type"
              placeholder="--Please select--"
              name="redemption_type"
              labelPosition="left"
              helpText="In how many subscription cycles this offer will be applied."
              value={values.redemption_type}
              onChange={({ name, values }) => {
                handleFormChange(name, values[0]);
              }}
              validationState={
                touched.redemption_type && errors?.redemption_type ? 'error' : 'none'
              }
              errorText={errors?.redemption_type}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(REDEMPTION_TYPE_OPTIONS).map((type) => (
                  <ActionListItem
                    key={type.name}
                    title={type.label}
                    value={type.name}
                    testID={`option-${type.name}`}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>

          {showNoOfCycles && (
            <TextInput
              isRequired
              type="number"
              label=" "
              labelPosition="left"
              name="no_of_cycles"
              placeholder="E.g. 3"
              isDisabled={isFormLocked}
              helpText="Number of cycles in which offer will be applied."
              marginBottom="spacing.7"
              value={values.no_of_cycles}
              onChange={({ name, value }) => {
                handleFormChange(name, value);
              }}
              validationState={touched.no_of_cycles && errors?.no_of_cycles ? 'error' : 'none'}
              errorText={errors?.no_of_cycles}
            />
          )}
        </>
      )}

      <div>
        {!hideDiscountType && (
          <Dropdown isDisabled={isFormLocked} marginTop="spacing.7" marginBottom="spacing.7">
            <SelectInput
              isRequired
              necessityIndicator="required"
              label="Discount Type"
              placeholder="--Select Type--"
              name="discount_type"
              labelPosition="left"
              value={values.discount_type}
              onChange={({ name, values }) => {
                handleFormChange(name, values[0]);
              }}
              validationState={touched.discount_type && errors?.discount_type ? 'error' : 'none'}
              errorText={errors?.discount_type}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(DISCOUNT_TYPES_OPTIONS).map((type) => (
                  <ActionListItem
                    key={type.name}
                    title={type.label}
                    value={type.name}
                    testID={`option-${type.name}`}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        )}

        {values.discount_type && (
          <TextInput
            isRequired={!isPercentDiscount}
            necessityIndicator={!isPercentDiscount ? 'required' : 'none'}
            label="Minimum Order amount"
            labelPosition="left"
            name="min_amount"
            placeholder="0.00"
            leadingIcon={RupeeIcon}
            helpText="Discount worth in cash"
            validationState={touched.min_amount && errors?.min_amount ? 'error' : 'none'}
            errorText={errors?.min_amount}
            isDisabled={isFormLocked}
            marginBottom="spacing.7"
            value={values.min_amount}
            onChange={({ name, value }) => {
              handleFormChange(name, value);
            }}
          />
        )}

        {(isNo_Cost_EmiDiscount || values.discount_type) && (
          <TextInput
            label="Maximum Order amount"
            labelPosition="left"
            name="max_order_amount"
            placeholder="0.00"
            leadingIcon={RupeeIcon}
            helpText="Discount worth in cash"
            marginBottom="spacing.7"
            value={values.max_order_amount}
            onChange={({ name, value }) => {
              handleFormChange(name, value);
            }}
            validationState={
              touched.max_order_amount && errors?.max_order_amount ? 'error' : 'none'
            }
            errorText={errors?.max_order_amount}
          />
        )}

        {isFlatDiscount && (
          <TextInput
            isRequired
            necessityIndicator="required"
            label="Discount Worth"
            labelPosition="left"
            name="flat_cashback"
            placeholder="0.00"
            leadingIcon={RupeeIcon}
            helpText="Discount worth in cash"
            validationState={touched.flat_cashback && errors?.flat_cashback ? 'error' : 'none'}
            errorText={errors?.flat_cashback}
            isDisabled={isFormLocked}
            marginBottom="spacing.7"
            value={values.flat_cashback}
            onChange={({ name, value }) => {
              handleFormChange(name, value);
            }}
          />
        )}

        {isPercentDiscount && (
          <React.Fragment>
            <TextInput
              isRequired
              necessityIndicator="required"
              label="Discount Worth"
              labelPosition="left"
              name="percent_rate"
              placeholder="0.00"
              trailingIcon={PercentIcon}
              helpText="Discount worth in Percent"
              validationState={touched.percent_rate && errors?.percent_rate ? 'error' : 'none'}
              errorText={errors?.percent_rate}
              isDisabled={isFormLocked}
              marginBottom="spacing.7"
              value={values.percent_rate}
              onChange={({ name, value }) => {
                handleFormChange(name, value);
              }}
            />

            <TextInput
              isRequired
              necessityIndicator="required"
              label={`Maximum ${isInstantOffer ? 'Discount' : 'Cashback'}`}
              labelPosition="left"
              name="max_cashback"
              placeholder="0.00"
              leadingIcon={RupeeIcon}
              helpText={`Maximum ${isInstantOffer ? 'discount' : 'cashback'} for this offer`}
              validationState={touched.max_cashback && errors?.max_cashback ? 'error' : 'none'}
              errorText={errors?.max_cashback}
              isDisabled={isFormLocked}
              value={values.max_cashback}
              onChange={({ name, value }) => {
                handleFormChange(name, value);
              }}
            />
          </React.Fragment>
        )}
      </div>
    </React.Fragment>
  );
}

export function validateDiscountType(val) {
  if (!val || val == '') {
    return 'Please select a discount type';
  }
  return false;
}

export function validatePercentRate(val) {
  if (!val) {
    return 'Please fill out this field';
  }

  val = parseFloat(val);
  if (val > 99.99 || val < 0.01) {
    return 'Percentage should be between 0 and 100';
  }

  const decimalPointError = validateDecimalPointValue(val);
  if (decimalPointError) return decimalPointError;
  return false;
}

export function validateFlatCashback(val, min_amount) {
  if (!val) return 'Please fill out this field';
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
}

export function validateMinAmount({
  val,
  flat_cashback,
  isPercentDiscount,
  max_order_amount,
  minAmount,
}) {
  if (!val && isPercentDiscount) {
    return false;
  }
  if (!val && !isPercentDiscount) return 'Please fill out this field';
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
}

export function validateMaxCashback(val) {
  if (!val) return 'Please fill out this field';
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
export function validateNoOfCycles(val) {
  if (!val) return 'Please fill out this field';
  return false;
}
export function validateMaxOrderAmount(val, min_amount) {
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
}

const DECIMAL_POINT_REGEX = '^[0-9]+(.[0-9][0-9]?)?$';
export function validateDecimalPointValue(val) {
  const isValid = new RegExp(DECIMAL_POINT_REGEX).test(val);

  if (!isValid) return 'Please enter number upto 2 decimal points';
  return false;
}
