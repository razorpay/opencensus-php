import React from 'react';

import Field, { SelectField, CheckField } from 'ui/Field';
import Form from 'ui/Form';
import { adminPost } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

NodalMoneyTransfer.title = 'Money Transfer (Nodal to Nodal)';

export default function NodalMoneyTransfer() {
  return (
    <Form class="full-span full-elements">
      <SelectField label="Destination" name="destination" required={true}>
        {Object.keys(options).map(opt => (
          <option value={opt} key={opt}>
            {options[opt]}
          </option>
        ))}
      </SelectField>

      <Field label="Gateway" name="gateway" />

      <Field
        label="Amount (in paise)"
        name="amount"
        infoMsg="Please enter the amount in paise"
        type="number"
      />

      <SelectField label="Channel" name="channel">
        {Object.keys(options).map(opt => (
          <option value={opt} key={opt}>
            {options[opt]}
          </option>
        ))}
      </SelectField>

      <AsyncButton
        text="Transfer"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          return adminPost({
            url: 'live/nodal/transfer',
            data,
          }).then(response => {
            if (response) {
              notifySuccess('Money transferred is successfull.');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}

const options = {
  kotak: 'Kotak',
  icici: 'ICICI',
  axis: 'Axis',
  yesbank: 'Yes Bank',
  hdfc: 'HDFC',
  rbl: 'RBL',
};
