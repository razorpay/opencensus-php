import React from 'react';
import Field, { FileField, SelectField, SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminFormUpload } from 'util/fetch';

const gateWayOptions = [
  'HDFC',
  'BillDesk',
  'Axis',
  'Ebs',
  'PayZapp',
  'Mobikwik',
  'Olamoney',
  'Kotak',
  'Paytm',
  'Freecharge',
  'Jiomoney',
  'SBI Buddy',
  'Netbanking AXIS',
  'Netbanking ICICI',
  'Netbanking Corporation',
  'Netbanking Federal',
  'Netbanking Rbl',
  'Netbanking Indusind',
  'Netbanking Pnb',
  'First Data',
  'Virtual Accounts Kotak',
];

UploadReconciliationFile.title = 'Upload Reconciliation File (Payment/Refund)';
export default function UploadReconciliationFile() {
  return (
    <Form>
      <header>{UploadReconciliationFile.title}</header>
      <SelectMode />
      {/* <Field
        name="name"
        label="File Count"
        name="c"
        type="number"
        min="1"
        max="10"
      /> */}
      <SelectField label="Gateway" name="gateway">
        {gateWayOptions.map((opt, idx) => (
          <option key={idx} value={opt.replace(/\s/g, '')}>
            {opt}
          </option>
        ))}
      </SelectField>
      <FileField multiple label="Attach Multiple Files" name="files" />
      <AsyncButton
        text="Upload"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          let files = document.querySelector('[name=files]').files || [];
          let form = {
            manual: 1,
            'attachment-count': files.length || 0,
            gateway: data.gateway,
          };
          for (let i = 0; i < files.length; i++) {
            if (files[i]) form['attachment-' + (i + 1)] = files[i];
          }

          return adminFormUpload(form, '/admin/' + data.mode + '/reconciliate')
            .then(response => {
              if (response.data.success) {
                notifySuccess('Reconciliation Response successful');
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
