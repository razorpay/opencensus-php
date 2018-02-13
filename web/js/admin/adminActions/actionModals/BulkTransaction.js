import React from 'react';
import AsyncButton from 'ui/AsyncButton';
import Field, { SelectField } from 'ui/Field';
import Form from 'ui/Form';
import { notifySuccess, closeModal } from 'common/modal';
import { adminPut } from 'common/fetch';

BulkTransaction.permissions = 'settlement_bulk_update';
BulkTransaction.title = 'Update Bulk Transaction';

export default function BulkTransaction() {
  return(
    <Form>
      <Field label="Merchand ID" name="merchant_id"/>
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
        onSubmit={body => {

          return adminPut({
              route_name: 'transaction_bulk_update',
              body,
          }).then(data => {
            if(data) {
              notifySuccess('Updated successfully.');
            }
            closeModal();
          })
        }}
      />
    </Form>
  );
}