import React from 'react';
import {
  DateTimeField,
  SelectField,
  CheckField,
  SelectMode,
  FromField,
  ToField,
} from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

const options = {
  bank: [
    'HDFC',
    'Kotak',
    'Axis',
    'ICICI',
    'Federal',
    'Corporation',
    'Indusind',
    'RBL',
    'PNB',
  ],
};

GenerateRefundsExcel.title = 'Generate Refund Excel';
export default function GenerateRefundsExcel() {
  return (
    <Form>
      <header>{GenerateRefundsExcel.title}</header>
      <DateTimeField
        label="Date"
        name="on"
        defaultValue={new Date().toISOString().split('T')[0]}
      />
      <FromField name="from" />
      <ToField name="to" />
      <br />
      <SelectField label="Bank" name="bank">
        {options.bank.map((opt, idx) => (
          <option key={idx} value={opt}>
            {opt}
          </option>
        ))}
      </SelectField>
      <SelectMode />
      <CheckField label="Send Email to self" name="email_self" />
      <AsyncButton
        text="Generate"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          let body = {
            bank: data.bank,
            mode: data.mode,
            method: 'netbanking',
          };
          if (data.to && data.from) {
            body.to = new Date(data.to).getTime();
            body.from = new Date(data.from).getTime();
          } else {
            body.on = data.on;
          }
          return adminPost({
            body,
            route_name: 'refund_generate_excel',
          })
            .then(response => {
              if (response.data.success) {
                notifySuccess(
                  'Refunds Excel Generated (count = ' +
                    response.data.count +
                    ')'
                );
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
