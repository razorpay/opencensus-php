import React from 'react';
import AsyncButton from 'ui/AsyncButton';
import Field, { SelectField } from 'ui/Field';
import Form from 'ui/Form';
import { notifySuccess, closeModal } from 'common/modal';
import { adminPut } from 'common/fetch';

BulkTransaction.permissions = 'edit_bulk_merchant_channel';
BulkTransaction.title = 'Merchant Bulk Update Channel';

export default function BulkTransaction() {
  return (
    <Form>
      <Field required label="Merchand IDs (Comma Separated)" name="merchant_ids" />
      <SelectField name="channel" label="Channel">
        <option value="">Select</option>
        <option value="kotak">kotak</option>
        <option value="icici">icici</option>
        <option value="axis">axis</option>
        <option value="yesbank">yesbank</option>
        <option value="hdfc">hdfc</option>
        <option value="rbl">rbl</option>
      </SelectField>
      <AsyncButton
        text="Generate"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          data.merchant_ids = data.merchant_ids.split(',').map(m => m.trim()).filter(Boolean);
          return adminPut({
            url: 'live/merchants/channel/bulk',
            data,
          }).then(data => {
            if (data) {
              notifySuccess('Updated successfully.');
            }
            closeModal();
          });
        }}
      />
    </Form>
  );
}
