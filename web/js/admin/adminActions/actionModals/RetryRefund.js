import React from 'react';
import Form from 'ui/Form';
import Field, { SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

RetryRefund.title = 'Retry Refund to Bank Account';
RetryRefund.permission = 'edit_payment_refund';
export default function RetryRefund() {
  return (
    <Form class="full-span">
      <Field label="Refund Id" type="text" name="id" required />
      <Field
        label="Beneficiary Name"
        type="text"
        name="beneficiary_name"
        required
      />
      <Field
        label="Account Number"
        type="text"
        name="account_number"
        required
      />
      <Field label="IFSC" type="text" name="ifsc_code" required />
      <SelectMode defaultValue="live" />
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          adminPost({
            route_name: 'refund_verify_failed',
            mode: body.mode,
            body: {
              bank_account: {
                account_number: body.account_number,
                ifsc_code: body.ifsc_code,
                beneficiary_name: body.beneficiary_name,
              },
            },
            url_params: {
              id: body.id,
            },
          }).then(response => {
            if (response) {
              notifySuccess('Refund successful');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
