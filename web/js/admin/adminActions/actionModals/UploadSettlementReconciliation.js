import React from 'react';
import { SelectMode, FileField, SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { notifySuccess, notifyError, closeModal } from 'common/modal';

import { adminFormUpload } from 'common/fetch';

UploadSettlementReconciliation.permission = 'settlement_bulk_update';
UploadSettlementReconciliation.title = 'Upload Settlement Reconciliation (UTR)';
export default function UploadSettlementReconciliation() {
  return (
    <Form class="full-span full-elements" style={{ width: '400px' }}>
      <SelectMode />
      <SelectField name="channel" label="Channel">
        <option value="">Select</option>
        <option value="kotak">kotak</option>
        <option value="icici">icici</option>
        <option value="axis">axis</option>
        <option value="yesbank">yesbank</option>
        <option value="hdfc">hdfc</option>
        <option value="rbl">rbl</option>
      </SelectField>
      <FileField label="Attach File" name="file" />
      <AsyncButton
        text="Upload"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          let file = document.querySelector('[name=file]').files[0];
          //- pass 'channel' as url param
          let channel = data.channel || '';

          if (channel === '') {
            notifyError('Please select a channel.');
            return;
          }

          if (!file) {
            notifyError('Please select a file.');
            return;
          }

          delete data.channel;

          let mode = data.mode;
          delete data.mode;

          let form = {
            ...data,
            file: file,
            auth: 'admin',
            method: 'POST',
            file_name: 'file',
          };

          return adminFormUpload(
            form,
            `/admin/api/${mode}/settlements/reconcile/${channel}`
          ).then(response => {
            if (response.data.success) {
              notifySuccess('API Request successful');
              closeModal();
            } else {
              notifyError(response.data.errors[0]);
            }
          });
        }}
      />
    </Form>
  );
}
