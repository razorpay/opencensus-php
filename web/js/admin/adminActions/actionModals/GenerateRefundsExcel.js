import React from 'react';
import {
  DateField,
  SelectField,
  CheckField,
  SelectMode,
  FromField,
  ToField,
} from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

const options = {
  bank: {
    HDFC: 'HDFC',
    Kotak: 'KKBK',
    Axis: 'UTIB',
    ICICI: 'ICIC',
    Federal: 'FDRL',
    Corporation: 'CORP',
    Indusind: 'INDB',
    RBL: 'RATN',
    PNB: 'PUNB',
  },
};

GenerateRefundsExcel.permission = 'create_netbanking_refund';
GenerateRefundsExcel.title = 'Generate Refunds Excel (Netbanking)';
export default function GenerateRefundsExcel() {
  return (
    <Form>
      <DateField label="Date" name="on" value={new Date()} />
      <FromField name="from" />
      <ToField name="to" />
      <br />
      <SelectField label="Bank" name="bank">
        {Object.keys(options.bank).map((opt, idx) => (
          <option key={idx} value={options.bank[opt]}>
            {opt}
          </option>
        ))}
      </SelectField>
      <SelectMode />
      <CheckField label="Send Email to self" name="email_self" />
      <br />
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
            body.to = new Date(moment(data.to, 'DD-MM-YYYY')).getTime() / 1000;
            body.from =
              new Date(moment(data.from, 'DD-MM-YYYY')).getTime() / 1000;
          } else {
            body.on = data.on;
          }
          return adminPost({
            body,
            route_name: 'refund_generate_excel',
          }).then(response => {
            if (response) {
              notifySuccess(`${data.bank} Refunds Excel Generated`);
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
