import React from 'react';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'common/fetch';

AuthorizeFailedPayment.permission = 'edit_authorized_failed_payment';
AuthorizeFailedPayment.title = 'Authorize Failed Payment';
export default function AuthorizeFailedPayment() {
  return (
    <Form>
      <Field label="Payment ID" name="payment" />
      <SelectMode />
      <br />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          return adminPost({
            url_params: {
              id: data.payment || '',
            },
            mode: data.mode,
            route_name: 'payment_authorize_failed',
          }).then(response => {
            if (response) {
              notifySuccess('Payment Authorized Successfully.');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
