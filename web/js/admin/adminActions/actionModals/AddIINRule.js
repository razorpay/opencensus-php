import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'common/fetch';

const options = {
  network: [
    '',
    'American Express',
    'Diners Club',
    'Discover',
    'JCB',
    'Maestro',
    'MasterCard',
    'RuPay',
    'Visa',
    'Union Pay',
    'Unknown',
  ],
  type: ['', 'Credit', 'Debit', 'Unknown'],
};

AddIINRule.permission = 'edit_iin_rule';
AddIINRule.title = 'Add IIN';
export default function AddIINRule() {
  return (
    <Form>
      <Field label="IIN (6 digit)" name="iin" required />
      <SelectField label="Network" name="network" required>
        {options.network.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <SelectField label="Type" name="type" required>
        {options.type.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <br />
      <Field label="Country (2 Character)" name="country" required />
      <Field label="Category" name="category" required />
      <Field label="Issuer" name="issuer" required />
      <br />
      <Field label="Issuer Name" name="issuer_name" />
      <Field label="Trivia" name="trivia" />
      <CheckField label="EMI" name="emi" />
      <br />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={body => {
          body.emi = body.emi ? 1 : 0;
          return adminPost({
            route_name: 'iin_add',
            body,
          }).then(response => {
            if (response) {
              notifySuccess('IIN added successfully.');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
