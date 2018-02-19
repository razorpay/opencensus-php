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
import user from 'admin/user';

import { adminPost } from 'common/fetch';

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
      <DateField
        label="Date"
        name="on"
        placeholder="YYYY-MM-DD"
        defaultValue={moment()}
        format="YYYY-MM-DD"
      />
      <FromField allowToday={true} />
      <ToField allowToday={true} />
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
          };

          if (data.to && data.from) {
            body.from =
              new Date(moment(data.from, 'DD-MM-YYYY')).getTime() / 1000;
            body.to = new Date(moment(data.to, 'DD-MM-YYYY')).getTime() / 1000;
          } else {
            body.on = data.on;
          }
          if (data.email_self) {
            body.email = user.email;
          }
          return adminPost({
            url: `${data.mode}/emi/generate/excel`,
            data: body,
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
