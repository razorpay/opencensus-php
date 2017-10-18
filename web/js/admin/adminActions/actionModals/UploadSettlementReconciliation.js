import React from 'react';
import axios from 'axios';
import { SelectMode, FileField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminFormUpload } from 'util/fetch';

UploadSettlementReconciliation.title = 'Upload Settlement Reconciliation (UTR)';
export default function UploadSettlementReconciliation() {
  return (
    <Form>
      <header>{UploadSettlementReconciliation.title}</header>
      <SelectMode />
      <FileField label="Attach File" type="file" name="file" />
      <AsyncButton
        text="Upload"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          let file = document.querySelector('[name=file]').files[0];
          let form = {
            ...data,
            file: file,
            auth: 'admin',
            method: 'POST',
            file_name: 'file',
          };

          return adminFormUpload(form, '/settlements/reconcile')
            .then(response => {
              if (response.data.success) {
                notifySuccess('API Request successful');
                closeModal();
              } else {
                response.data.errors.map(error => notifyError(error));
              }
            })
            .catch(err => {
              notifyError('The API request failed on the dashboard side.');
            });
        }}
      />
    </Form>
  );
}
