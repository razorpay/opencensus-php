import React from 'react';
import Field, { DateField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminFetch } from 'util/fetch';

IRCTC.title = 'IRCTC';
export default function IRCTC() {
  return (
    <Form class="full-span full-elements" style={{ minHeight: '300px' }}>
      <DateField
        label="Date"
        name="on"
        value={new Date()}
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
          return adminFetch({
            route_name: 'reports_refund_irctc',
            merchant_id: data.merchant_id,
            query_params: {
              email: data.email,
              on: data.on,
              mode: data.mode,
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
