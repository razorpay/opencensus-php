/* eslint-disable consistent-return */
import React from 'react';

import Input from 'common/new-ui/Input';
import { OFFER_TYPES_OPTIONS, OFFER_TYPES } from 'merchant/views/Offers/constants';

export default ({ formData, isFormLocked, hideType }) => {
  let offerTypeDescription;

  if (formData.type === OFFER_TYPES.Cashback) {
    offerTypeDescription =
      'Cashbacks need to be processed by the provider (Wallet providers, Banks etc). Please create Cashback Offers only if you have an agreement in place with them';
  }

  return (
    <React.Fragment>
      <Input
        required
        autoFocus
        label="Offer Name"
        name="name"
        autoComplete="false"
        placeholder="Example: New Year Sale (This name appears on your dashboard)"
        defaultValue={formData.name}
        maxLength={50}
        validator={validateName}
        disabled={isFormLocked}
      />

      <Input
        required
        label="Display Text"
        name="display_text"
        placeholder="10% off on all HDFC Debit Cards (This appears on checkout for your customers)"
        defaultValue={formData.display_text}
        maxLength={250}
        validator={validateDisplayText}
        disabled={isFormLocked}
      />

      <Input.Textarea
        required
        label="Terms"
        name="terms"
        placeholder="Terms and conditions for offer"
        description="Enter offer terms and conditions"
        defaultValue={formData.terms}
        maxLength={250}
        validator={validateTerms}
        disabled={isFormLocked}
      />

      {!hideType && (
        <Input.Select
          required
          name="type"
          label="Offer Type"
          defaultValue={formData.type}
          options={OFFER_TYPES_OPTIONS}
          description={offerTypeDescription}
          validator={validateDiscountType}
          disabled={isFormLocked}
        />
      )}
    </React.Fragment>
  );
};

export function validateName(val) {
  if (!val || val.length < 4) {
    return 'Short name should be at least of 4 characters';
  }
}

export function validateDisplayText(val) {
  if (!val || val.length < 4) {
    return 'Short description should be at least of 4 characters';
  }
}
export function validateTerms(val) {
  if (!val || val.length < 4) {
    return 'Offer terms should contain at least of 4 characters';
  }
}

export function validateDiscountType(val) {
  if (!val || val == '') {
    return 'Please select a discount type';
  }
}
