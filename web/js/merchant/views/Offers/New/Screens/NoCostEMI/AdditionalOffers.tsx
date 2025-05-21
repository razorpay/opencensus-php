import React from 'react';
import {
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  PercentIcon,
  RupeeIcon,
  TextInput,
  Checkbox,
  CheckboxGroup,
  Tabs,
  TabList,
  TabItem,
  Box,
} from '@razorpay/blade/components';

import {
  ADDITIONAL_OFFER_OPTIONS,
  ADDITIONAL_OFFER_VALUES,
  DISCOUNT_TYPES,
} from 'merchant/views/Offers/constants';
import { validateFlatCashback, validateMaxCashback, validatePercentRate } from 'merchant/views/Offers/New/Screens/DiscountTypes';

export default function AdditionalOffers({
  isFormLocked,
  values,
  setFieldTouched,
  setFieldValue,
  errors,
  touched,
  isInstantOffer,
}) {
  const isNo_Cost_EmiDiscount = values.discount_type === DISCOUNT_TYPES.NO_COST_EMI;

  const isPercentDiscount =
    values.discount_type === DISCOUNT_TYPES.PERCENT ||
    (isNo_Cost_EmiDiscount && values.additional_offer_discount_type === 2);

  const isFlatDiscount =
    values.discount_type === DISCOUNT_TYPES.FLAT ||
    (isNo_Cost_EmiDiscount &&
      (!values.additional_offer_discount_type || values.additional_offer_discount_type === 1));

  const handleFormChange = (name, value) => {
    setFieldTouched(name);
    setFieldValue(name, value);

    // Clear related fields when changing offer type
    if (name === 'additional_offer') {
      setFieldValue('additional_offer_discount_type', 1); // Set default to flat
      setFieldValue('flat_cashback', null);
      setFieldValue('percent_rate', null);
      setFieldValue('max_cashback', null);
    }

    // Clear related fields when changing discount type
    if (name === 'additional_offer_discount_type') {
      if (!value || value === 1) {
        setFieldValue('percent_rate', null);
        setFieldValue('max_cashback', null);
      } else if (value === 2) {
        setFieldValue('flat_cashback', null);
      }
    }
  };

  // Only validate fields if additional offer is selected
  if (values.additional_offer) {
    if (isPercentDiscount) {
      errors.percent_rate = validatePercentRate(values.percent_rate);
      errors.max_cashback = validateMaxCashback(values.max_cashback);
    } else {
      delete errors.percent_rate;
      delete errors.max_cashback;
    }

    if (isFlatDiscount) {
      errors.flat_cashback = validateFlatCashback(values.flat_cashback, values.min_amount);
    } else {
      delete errors.flat_cashback;
    }
  } else {
    // Clear all validation errors if no additional offer selected
    delete errors.percent_rate;
    delete errors.max_cashback;
    delete errors.flat_cashback;
  }

  return (
    <React.Fragment>
      <Dropdown isDisabled={isFormLocked} marginTop="spacing.7" marginBottom="spacing.7">
        <SelectInput
          label="Additional Offer"
          placeholder="--Please select--"
          name="additional_offer"
          labelPosition="left"
          helpText={
            values.additional_offer
              ? values.additional_offer === ADDITIONAL_OFFER_VALUES.INSTANT_DISCOUNT
                ? 'This offer will be applied on top of the EMI subvention.'
                : 'This offer will be applied on top of the EMI subvention. Cashbacks need to be processed by the provider (Wallet providers, Banks etc). Please create Cashback Offers only if you have an agreement in place with them.'
              : ''
          }
          value={values.additional_offer}
          onChange={({ name, values }) => {
            handleFormChange(name, values[0]);
          }}
          validationState={touched.additional_offer && errors?.additional_offer ? 'error' : 'none'}
          errorText={errors?.additional_offer}
        />
        <DropdownOverlay>
          <ActionList>
            {Object.values(ADDITIONAL_OFFER_OPTIONS).map((type) => (
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
      {values.additional_offer && (
        <>
          <CheckboxGroup
            isRequired
            necessityIndicator="required"
            name="tenures_applicable"
            label="Tenures Applicable"
            labelPosition="left"
            display="flex"
            multiple
            value={values.tenures_applicable || []}
            onChange={({ values }) => {
              handleFormChange('tenures_applicable', values);
            }}
          >
            <Box display="flex" flexDirection="row">
              {values.emi_durations?.map((tenure, index) => (
                <Checkbox
                  key={tenure}
                  value={tenure}
                  marginLeft={index === 0 ? 'spacing.0' : 'spacing.4'}
                >
                  {tenure} Months
                </Checkbox>
              ))}
            </Box>
          </CheckboxGroup>
          <Box display="flex" flex="row" marginY="spacing.7">
            <Box width="19%">
              <label>Discount</label>
            </Box>
            <Box display="flex" flexgrow={1}>
              <Tabs
                isRequired
                necessityIndicator="required"
                variant="filled"
                value={values.additional_offer_discount_type}
                orientation="horizontal"
                onChange={(selectedTab) => {
                  handleFormChange('additional_offer_discount_type', selectedTab);
                }}
                isFullWidthTabItem
              >
                <TabList width="100%" marginx="spacing.7">
                  <TabItem value={1}>Flat</TabItem>
                  <TabItem value={2}>Percentage</TabItem>
                </TabList>
              </Tabs>
            </Box>
          </Box>
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
        </>
      )}
    </React.Fragment>
  );
}

export function generateAdditionalOfferString(values, currencySymbol) {
  if (!values.additional_offer) {
    return '';
  }

  let discountString = '';
  const tenures = values.tenures_applicable || [];

  if (values.additional_offer_discount_type === 1) {
    // Flat discount
    discountString = `Flat discount of ${currencySymbol}${values.flat_cashback} on a minimum purchase of ${currencySymbol}${values.min_amount}`;
  } else if (values.additional_offer_discount_type === 2) {
    // Percentage discount
    discountString = `${values.percent_rate}% discount upto ${currencySymbol}${values.max_cashback} on a minimum purchase of ${currencySymbol}${values.min_amount}`;
  }

  if (tenures.length > 0) {
    const tenureString = tenures.map((tenure) => `${tenure} month`).join(' & ');
    discountString += `; ${tenureString} tenures`;
  }

  return discountString;
}
