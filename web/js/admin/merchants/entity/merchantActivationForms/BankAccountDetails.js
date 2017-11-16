import React, { Component } from 'react';

import { beneficiaryStateMap } from '../entity-resources';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';

export default ({ merchant_details: merchantDetails }) => (
  <div class="container">
    <header>Bank Account Details</header>
    {!merchantDetails ? (
      <div class="spinner center" />
    ) : (
      <Form>
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
          label="Beneficiary Address Line 1"
          name="bank_beneficiary_address1"
          type="textarea"
          defaultValue={merchantDetails.bank_beneficiary_address1}
          required
        />

        <Field
          label="Beneficiary Address Line 2"
          name="bank_beneficiary_address2"
          type="textarea"
          defaultValue={merchantDetails.bank_beneficiary_address2}
        />

        <Field
          label="Beneficiary Address Line 3"
          name="bank_beneficiary_address3"
          type="textarea"
          defaultValue={merchantDetails.bank_beneficiary_address3}
        />

        <Field
          label="Beneficiary Address City"
          name="bank_beneficiary_city"
          defaultValue={merchantDetails.bank_beneficiary_city}
          required
        />

        <SelectField
          label="Beneficiary State"
          name="bank_beneficiary_state"
          defaultValue={merchantDetails.bank_beneficiary_state}
          required
        >
          {Object.keys(beneficiaryStateMap).map(key => (
            <option key={key} value={key}>
              {beneficiaryStateMap[key]}
            </option>
          ))}
        </SelectField>

        <Field
          label="Beneficiary Address Pincode"
          name="bank_beneficiary_pin"
          defaultValue={merchantDetails.bank_beneficiary_pin}
          required
        />

        <Field
          label="Bank Account Type"
          name="bank_account_type"
          defaultValue={merchantDetails.bank_account_type}
          required
        />

        <Field
          label="Branch Address"
          name="bank_branch"
          defaultValue={merchantDetails.bank_account_type}
          infoMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
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
