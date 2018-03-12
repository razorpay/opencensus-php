import React, { Component } from 'react';

import { beneficiaryStateMap } from '../entity-resources';

import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';

export default ({ merchant_details: merchantDetails, title }) => (
  <div class="container">
    <header class="m-b">{title}</header>
    {!merchantDetails ? (
      <div class="spinner center m-t" />
    ) : (
      <Form class="full-span full-elements limited">
        <div class="some">
          <Field
            label="Name of Bank"
            name="bank_name"
            defaultValue={merchantDetails.bank_name}
            helpMsg="Do not panic if this is empty or incorrect. We don't ask this field any more."
            disabled
          />
          <CheckField label="Has Issue" />
        </div>

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
          label="Beneficiary Address Line 1"
          name="bank_beneficiary_address1"
          type="textarea"
          defaultValue={merchantDetails.bank_beneficiary_address1}
          disabled
        />

        <Field
          label="Beneficiary Address Line 2"
          name="bank_beneficiary_address2"
          type="textarea"
          defaultValue={merchantDetails.bank_beneficiary_address2}
          disabled
        />

        <Field
          label="Beneficiary Address Line 3"
          name="bank_beneficiary_address3"
          type="textarea"
          defaultValue={merchantDetails.bank_beneficiary_address3}
          disabled
        />

        <Field
          label="Beneficiary Address City"
          name="bank_beneficiary_city"
          defaultValue={merchantDetails.bank_beneficiary_city}
          disabled
        />

        <SelectField
          label="Beneficiary State"
          name="bank_beneficiary_state"
          defaultValue={merchantDetails.bank_beneficiary_state}
          disabled
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
