import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminFetch } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

VerifyPayment.title = 'Verify Payment';
export default function VerifyPayment() {
  return (
    <Form class="full-span">
      <Field label="Payment Id" type="text" name="id" required />
      <SelectField label="Mode" name="mode" required>
        <option value="live">Live</option>
        <option value="test">Test</option>
      </SelectField>
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={({ id, mode }) =>
          adminFetch({
            route_name: 'payment_verify',
            mode,
            url_params: {
              id,
            },
          }).then(response => {
            if (response) {
              notifySuccess('Payment Verified successfully');
              closeModal();
            }
          })
        }
      />
    </Form>
  );
}
