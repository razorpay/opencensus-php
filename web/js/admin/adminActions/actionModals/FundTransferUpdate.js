import React from 'react';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { TextAreaField } from 'ui/Field';

import { adminPatch } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

FundTransferUpdate.title = 'Fund Transfer Update';
FundTransferUpdate.permission = 'settlement_bulk_update';

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
          return adminPatch({
            url: 'live/fund_transfer_attempts',
            data: body.json_dump,
          }).then(response => {
            if (response) {
              notifySuccess('Funds updated successfully.');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
