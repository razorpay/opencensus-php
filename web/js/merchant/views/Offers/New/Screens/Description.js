/* eslint-disable consistent-return */
import React from 'react';
import {
  TextInput,
  TextArea,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { OFFER_TYPES_OPTIONS, OFFER_TYPES } from 'merchant/views/Offers/constants';
export default ({
  isFormLocked,
  hideType,
  values,
  setFieldTouched,
  setFieldValue,
  errors,
  touched,
}) => {
  let offerTypeDescription;

  if (values.type === OFFER_TYPES.Cashback) {
    offerTypeDescription =
      'Cashbacks need to be processed by the provider (Wallet providers, Banks etc). Please create Cashback Offers only if you have an agreement in place with them';
  }

  const handleFormChange = (name, value) => {
    setFieldTouched(name);
    setFieldValue(name, value);
  };

  errors.name = validateName(values.name);
  errors.display_text = validateDisplayText(values.display_text);
  errors.terms = validateTerms(values.terms);
  !hideType
    ? (errors.type = validateDiscountType(values.type))
    : Object.fromEntries(Object.entries(errors).filter(([key]) => key !== 'type'));

  return (
    <React.Fragment>
      <TextInput
        isRequired
        necessityIndicator="required"
        autoFocus
        label="Offer Name"
        labelPosition="left"
        name="name"
        placeholder="Example: New Year Sale (This name appears on your dashboard)"
        maxCharacters={50}
        isDisabled={isFormLocked}
        marginBottom="spacing.4"
        onChange={({ name, value }) => {
          handleFormChange(name, value);
        }}
        value={values.name}
        validationState={touched.name && errors.name ? 'error' : 'none'}
        errorText={errors?.name}
      />

      <TextInput
        isRequired
        necessityIndicator="required"
        label="Display Text"
        labelPosition="left"
        name="display_text"
        placeholder="10% off on all HDFC Debit Cards (This appears on checkout for your customers)"
        maxCharacters={250}
        validationState={touched.display_text && errors?.display_text ? 'error' : 'none'}
        errorText={errors?.display_text}
        isDisabled={isFormLocked}
        marginBottom="spacing.4"
        onChange={({ name, value }) => {
          handleFormChange(name, value);
        }}
        value={values.display_text}
      />

      <TextArea
        isRequired
        necessityIndicator="required"
        label="Terms"
        name="terms"
        placeholder="Terms and conditions for offer"
        helpText="Enter offer terms and conditions"
        labelPosition="left"
        maxCharacters={250}
        validationState={touched.terms && errors?.terms ? 'error' : 'none'}
        errorText={errors?.terms}
        isDisabled={isFormLocked}
        marginBottom="spacing.7"
        onChange={({ name, value }) => {
          handleFormChange(name, value);
        }}
        value={values.terms}
      />

      {!hideType && (
        <Dropdown isDisabled={isFormLocked}>
          <SelectInput
            label="Offer Type"
            placeholder="--Please select--"
            name="type"
            labelPosition="left"
            necessityIndicator="required"
            isRequired
            helpText={offerTypeDescription}
            value={values.type}
            onChange={({ name, values }) => {
              handleFormChange(name, values[0]);
            }}
            validationState={touched?.type && errors?.type ? 'error' : 'none'}
            errorText={errors?.type}
          />
          <DropdownOverlay>
            <ActionList>
              {Object.values(OFFER_TYPES_OPTIONS).map((type) => (
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
    </React.Fragment>
  );
};

export function validateName(val) {
  if (!val) return 'Please fill out this field';
  if (val.length < 4) {
    return 'Short name should be at least of 4 characters';
  }
  return false;
}

export function validateDisplayText(val) {
  if (!val) return 'Please fill out this field';
  if (val.length < 4) {
    return 'Short description should be at least of 4 characters';
  }
  return false;
}
export function validateTerms(val) {
  if (!val) return 'Please fill out this field';
  if (val.length < 4) {
    return 'Offer terms should contain at least of 4 characters';
  }
  return false;
}

export function validateDiscountType(val) {
  if (!val) {
    return 'Please select a discount type';
  }
  return false;
}
