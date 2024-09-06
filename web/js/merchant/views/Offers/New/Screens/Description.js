/* eslint-disable consistent-return */
import React from 'react';
import { TextInput, TextArea } from '@razorpay/blade/components';

export default ({ isFormLocked, values, setFieldTouched, setFieldValue, errors, touched }) => {
  function handleFormChange(name, value) {
    setFieldTouched(name);
    setFieldValue(name, value);
  }

  errors.name = validateName(values.name);
  errors.display_text = validateDisplayText(values.display_text);
  errors.terms = validateTerms(values.terms);

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
        testID="offer-name"
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
