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
          infoMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
        />

        <Field
          label="Bank Account Number"
          name="bank_account_number"
          defaultValue={merchantDetails.bank_account_number}
          infoMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
          required
        />

        <Field
          label="Beneficiary Name"
          name="bank_account_name"
          defaultValue={merchantDetails.bank_account_name}
          infoMsg="Should be same as business/individual name"
          required
        />

        <Field
          label="Bank Account Type"
          name="bank_account_type"
          defaultValue={merchantDetails.bank_account_type}
          required
        />

        <Field
          label="Branch IFSC Code"
          name="bank_branch_ifsc"
          defaultValue={merchantDetails.bank_branch_ifsc}
          placeholder="IFSC Code of the Bank Branch"
          infoMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
        />
      </Form>
    )}
  </div>
);
