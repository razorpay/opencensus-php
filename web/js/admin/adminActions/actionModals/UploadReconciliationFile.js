import React from 'react';
import Field, {
  FileField,
  SelectField,
  SelectMode,
  TextAreaField,
} from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { notifySuccess, notifyError, closeModal } from 'common/modal';
import { splitAndFilter } from 'common/util';
import { adminFormUpload3 } from 'common/fetch';

const gateWayOptions = [
  'HDFC',
  'BillDesk',
  'Airtel',
  'Amazonpay',
  'Amex',
  'Axis',
  'Ebs',
  'PayZapp',
  'Mobikwik',
  'PayuMoney',
  'Olamoney',
  'Kotak',
  'Paytm',
  'Freecharge',
  'Jiomoney',
  'Hitachi',
  'SBI Buddy',
  'UpiSbi',
  'UpiIcici',
  'UpiHulk',
  'Mpesa',
  'Netbanking Axis',
  'Netbanking Icici',
  'Netbanking Corporation',
  'Netbanking Federal',
  'Netbanking Idfc',
  'Netbanking Rbl',
  'Netbanking Indusind',
  'Netbanking Pnb',
  'Netbanking Equitas',
  'First Data',
  'Virtual Accounts Kotak',
  'Virtual Accounts Yes Bank',
  'Netbanking Bob',
  'Netbanking Pnb',
  'Netbanking Obc',
  'Netbanking Csb',
  'Netbanking Hdfc',
  'Atom',
  'CardFssBob',
  'CardFssHdfc',
  'UpiHdfc',
];

const optionValueMap = {
  'Virtual Accounts Kotak': 'VirtualAccKotak',
  'Virtual Accounts Yes Bank': 'VirtualAccYesBank',
};

UploadReconciliationFile.permission = 'add_reconciliation_file';
UploadReconciliationFile.title = 'Upload Reconciliation File (Payment/Refund)';
export default function UploadReconciliationFile() {
  return (
    <Form class="full-span full-elements" style={{ width: '400px' }}>
      <SelectMode />
      <SelectField label="Gateway" name="gateway">
        {gateWayOptions.map((opt, idx) => (
          <option
            key={idx}
            value={optionValueMap[opt] || opt.replace(/\s/g, '')}
          >
            {opt}
          </option>
        ))}
      </SelectField>
      <FileField multiple label="Attach Multiple Files" name="files" />
      <TextAreaField
        label="Force Authorize"
        name="force_authorize"
        placeholder="Enter comma separated ids"
      />
      <AsyncButton
        text="Upload"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          let files = document.querySelector('[name=files]').files || [];
          if (!files.length) {
            notifyError('Please select a file');
            return;
          }
          let form = {
            manual: 1,
            'attachment-count': files.length || 0,
            gateway: data.gateway,
          };
          if (data.force_authorize) {
            form.force_authorize = splitAndFilter(data.force_authorize, ',');
          }
          for (let i = 0; i < files.length; i++) {
            if (files[i]) form['attachment-' + (i + 1)] = files[i];
          }

          return adminFormUpload3(
            form,
            '/admin/' + data.mode + '/reconciliate'
          ).then(response => {
            if (response.data.success) {
              notifySuccess('Reconciliation Response successful');
              closeModal();
            } else {
              notifyError(response.data.errors.join(', '));
            }
          });
        }}
      />
    </Form>
  );
}
