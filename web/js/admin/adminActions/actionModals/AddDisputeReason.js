import React from 'react';
import Field, { SelectField } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'common/fetch';

const options = {
  network: [
    '',
    'American Express',
    'RZP',
    'Discover',
    'JCB',
    'Maestro',
    'MasterCard',
    'RuPay',
    'Visa',
    'Union Pay',
    'Unknown',
  ],
};

AddDisputeReason.title = 'Add Dispute Reason';
export default function AddDisputeReason() {
  return (
    <Form>
      <SelectField label="Network" name="network">
        {options.network.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <Field label="Code" name="code" />
      <Field label="Description" name="description" />
      <br />
      <Field label="Gateway Code" name="gateway_code" />
      <Field label="Gateway Description" name="gateway_description" />
      <br />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={body => {
          return adminPost({
            body,
            route_name: 'dispute_reason_create',
          }).then(response => {
            if (response) {
              notifySuccess(
                'Dispute Reason added successfully. Response: ' +
                  JSON.stringify(response)
              );
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
