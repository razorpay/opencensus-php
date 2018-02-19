import React from 'react';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'common/fetch';

// TODO: TEST what if url is patments/authorize_failed
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
          let url = data.payment
            ? `payments/{data.payment}/authorize_failed`
            : `payments/authorize_failed`;
          return adminPost(data.mode + '/' + url).then(response => {
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
