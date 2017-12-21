import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

const options = {
  banks: [
    'HDFC',
    'RBL',
    'Axis',
    'Kotak',
    'ICICI',
    'Federal',
    'Indusind',
    'Standard Chartered',
  ],
  duration: [3, 6, 9, 12, 15, 18, 21, 24],
  methods: ['', 'card'],
  subvention: ['', 'customer', 'merchant'],
};

AddEMIPlan.permission = 'create_emi_plan';
AddEMIPlan.title = 'Add EMI Plan';
export default function AddEMIPlan() {
  return (
    <Form>
      <SelectField label="Banks" name="bank">
        {options.banks.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <SelectField label="Duration (Months)" name="duration">
        {options.duration.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <SelectField label="Methods" name="methods">
        {options.methods.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <br />
      <Field label="Interest Rate" placeholder="1250" name="rate" />
      <SelectField label="Subvention" name="subvention">
        {options.subvention.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <Field
        label="Merchant Payback"
        placeholder="1250"
        type="number"
        name="merchant_payback"
      />
      <br />
      <Field label="Issuer Plan ID" name="issuer_plan_id" />
      <Field
        label="Min Amount"
        placeholder="1250"
        type="number"
        name="min_amount"
      />
      <br />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={body => {
          return adminPost({
            body,
            route_name: 'emi_plan_add',
          }).then(response => {
            if (response) {
              notifySuccess('EMI Plan added successfully');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
