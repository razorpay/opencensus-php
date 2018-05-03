import React from 'react';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { TextAreaField } from 'ui/Field';

import { adminPatch } from 'common/fetch';
import { notifySuccess, notifyError, closeModal } from 'common/modal';

FundTransferUpdate.title = 'Fund Transfer Update';

export default function FundTransferUpdate() {
  return (
    <Form class="full-span full-elements" style={{ width: '600px' }}>
      <TextAreaField label="JSON:" name="json_dump" />
      <AsyncButton
        text="Update"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          if (body.json_dump) {
            let data = null;

            try {
              data = JSON.parse(body.json_dump);
            } catch (err) {
              return notifyError('Please enter a valid JSON.');
            }

            return adminPatch({
              url: 'live/fund_transfer_attempts',
              data: data,
            }).then(response => {
              if (response) {
                notifySuccess('Funds updated successfully.');
                closeModal();
              }
            });
          } else {
            notifyError('Please enter the json data to proceed.');
          }
        }}
      />
    </Form>
  );
}
