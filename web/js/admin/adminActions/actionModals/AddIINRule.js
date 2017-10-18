import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

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

AddIINRule.title = 'Add IIN';
export default function AddIINRule() {
  return (
    <Form>
      <header>{AddIINRule.title}</header>
      <Field label="IIN (6 digit)" name="iin" />
      <SelectField label="Network" name="network">
        {options.network.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <SelectField label="Type" name="type">
        {options.type.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <br />
      <Field label="Country (2 Character)" name="country" />
      <Field label="Category" name="category" />
      <Field label="Issuer" name="issuer" />
      <br />
      <Field label="Issuer Name" name="issuer_name" />
      <CheckField label="EMI" name="emi" />
      <Field label="Trivia" name="trivia" />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          data.emi = data.emi ? 1 : 0;
          return adminPost({
            data: { body: data, route_name: 'iin_add' },
          })
            .then(response => {
              if (response.data.success) {
                notifySuccess('IIN added successfully.');
                closeModal();
              } else {
                response.data.errors.map(error => notifyError(error));
              }
            })
            .catch(err => {
              notifyError(JSON.stringify(err.response));
            });
        }}
      />
    </Form>
  );
}
