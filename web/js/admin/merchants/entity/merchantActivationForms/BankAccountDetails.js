import React, { Component } from 'react';

import { beneficiaryStateMap } from '../entity-resources';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';

export default ({ merchant_details: merchantDetails, title }) => (
  <div class="container">
    <header class="m-b">{title}</header>
    {!merchantDetails ? (
      <div class="spinner center m-t" />
    ) : (
      <Form class="full-span full-elements limited">
        <Field
          label="Name of Bank"
          name="bank_name"
          defaultValue={merchantDetails.bank_name}
          helpMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
          disabled
        />

        <Field
          label="Bank Account Number"
          name="bank_account_number"
          defaultValue={merchantDetails.bank_account_number}
          helpMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
          disabled
        />

        <Field
          label="Beneficiary Name"
          name="bank_account_name"
          defaultValue={merchantDetails.bank_account_name}
          helpMsg="Should be same as business/individual name"
          disabled
        />

        <Field
          label="Branch IFSC Code"
          name="bank_branch_ifsc"
          defaultValue={merchantDetails.bank_branch_ifsc}
          helpMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
          disabled
        />
      </Form>
    )}
  </div>
);
