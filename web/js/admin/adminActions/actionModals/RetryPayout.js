import React from 'react';
import Form from 'ui/Form';
import { SelectMode, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal, notifyError } from 'common/modal';

RetryPayout.title = 'Retry Payouts';
RetryPayout.permission = 'retry_settlement';
export default function RetryPayout() {
  return (
    <Form class="full-span">
      <TextAreaField
        label="Payout ID's"
        name="payout_ids"
        placeholder="Please enter comma(,) seperated payout public id's"
      />
      <SelectMode defaultValue="live" />
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          adminPost({
            url: `${body.mode}/payouts/retry`,
            data: {
              ids: body.payout_ids ? body.payout_ids.split(',') : [],
            },
          }).then(response => {
            if (response) {
              notifySuccess('Payout retry successful');
            } else {
              notifyError('Unexpected error happened');
            }
            closeModal();
          });
        }}
      />
    </Form>
  );
}
