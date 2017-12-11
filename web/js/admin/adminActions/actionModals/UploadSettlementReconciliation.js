import React from 'react';
import { SelectMode, FileField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { notifySuccess, notifyError, closeModal } from 'common/modal';

import { adminFormUpload } from 'util/fetch';

UploadSettlementReconciliation.permission = 'add_settlement_reconciliation';
UploadSettlementReconciliation.title = 'Upload Settlement Reconciliation (UTR)';
export default function UploadSettlementReconciliation() {
  return (
    <Form class="full-span full-elements" style={{ width: '400px' }}>
      <SelectMode />
      <FileField label="Attach File" name="file" />
      <AsyncButton
        text="Upload"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          let file = document.querySelector('[name=file]').files[0];

          if (!file) {
            notifyError('Please select a file.');
            return;
          }

          let form = {
            ...data,
            file: file,
            auth: 'admin',
            method: 'POST',
            file_name: 'file',
          };

          return adminFormUpload(form, '/settlements/reconcile').then(
            response => {
              if (response.data.success) {
                notifySuccess('API Request successful');
                closeModal();
              } else {
                notifyError(response.data.errors[0]);
              }
            }
          );
        }}
      />
    </Form>
  );
}
