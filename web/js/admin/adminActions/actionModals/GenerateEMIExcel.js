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
    Kotak: 'KKBK',
    Axis: 'UTIB',
    Indusind: 'INDB',
    RBL: 'RATN',
    SCBL: 'SCBL',
    ICICI: 'ICIC',
  },
};

GenerateRefundsExcel.permission = 'create_emi_files';
GenerateRefundsExcel.title = 'Generate EMI Excel';
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
          const tzGMTToIST = 19800;

          let body = {
            bank: data.bank,
            mode: data.mode,
          };

          if (data.to && data.from) {
            // Date from the date api is in GMT
            let fromInGMT = new Date(data.from).getTime() / 1000;
            let toInGMT = new Date(data.to).getTime() / 1000;

            // Subtract 19800 from GMT to convert timestamps to IST
            let fromInIST = fromInGMT - tzGMTToIST;
            let toInIST = toInGMT - tzGMTToIST;

            body.to = toInIST;
            body.from = fromInIST;
          } else {
            body.on = data.on;
          }
          return adminPost({
            route_name: 'emi_generate_excel',
            body,
            mode: data.mode,
          }).then(response => {
            if (response) {
              notifySuccess(
                `${data.bank} Refunds EMI Excel Generated. (Count = ${
                  response[data.bank].count
                })`
              );
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
