import React from 'react';
import Field, { DateField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import fetch from 'common/fetch';

// TODO: TEST mode to be sent in query params or just url?
IRCTC.title = 'IRCTC';
export default function IRCTC() {
  return (
    <Form class="full-span full-elements" style={{ minHeight: '300px' }}>
      <DateField
        label="Date"
        fieldClass="irctc-form m-l"
        name="on"
        placeholder="YYYY-MM-DD"
        defaultValue={moment()}
        format="YYYY-MM-DD"
      />
      <Field label="Merchant ID" name="merchant_id" />
      <Field label="Email" name="email" type="email" />
      <SelectMode />
      <AsyncButton
        text="Save"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          return fetch({
            url: `/admin/api/${data.mode}_${
              data.merchant_id
            }/reports/refund/irctc`,
            params: {
              email: data.email,
              on: data.on,
            },
          }).then(response => {
            if (response) {
              notifySuccess('Report will be sent to the email provided');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
