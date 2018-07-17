import React from 'react';
import Form from 'ui/Form';
import { SelectField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal, notifyError } from 'common/modal';

BulkVerifyPayments.title = 'Bulk Verify Payments';
export default function BulkVerifyPayments() {
  return (
    <Form class="full-span bulk-verify-payments">
      <TextAreaField
        label="Payment Ids"
        type="text"
        name="ids"
        required
        placeholder="Please enter comma separated payment ids for bulk verification."
      />
      <SelectField label="Mode" name="mode" required>
        <option value="live">Live</option>
        <option value="test">Test</option>
      </SelectField>
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={({ ids, mode }) => {
          if (ids) {
            adminPost({
              url: `${mode}/payments/verify/bulk`,
              data: {
                payment_ids: ids.split(','),
              },
            }).then(response => {
              if (response) {
                notifySuccess('Payments Verified successfully');
                closeModal();
              } else {
                notifyError(response.data.errors[0]);
              }
            });
          } else {
            notifyError('Payment Ids field is mandatory.');
          }
        }}
      />
    </Form>
  );
}
