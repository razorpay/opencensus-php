import React from 'react';
import axios from 'axios';
import { SelectMode, FileField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { notifySuccess, closeModal } from 'common/modal';

import { adminFormUpload } from 'util/fetch';

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
          let form = {
            ...data,
            file: file,
            auth: 'admin',
            method: 'POST',
            file_name: 'file',
          };

          return adminFormUpload(form, '/settlements/reconcile').then(
            response => {
              if (response) {
                notifySuccess('API Request successful');
                closeModal();
              }
            }
          );
        }}
      />
    </Form>
  );
}
