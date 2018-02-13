import React from 'react';
import AsyncButton from 'ui/AsyncButton';
import { SelectField, TextAreaField } from 'ui/Field';
import Form from 'ui/Form';
import { notifySuccess, closeModal } from 'common/modal';
import { adminPut } from 'common/fetch';

SettlementBulkChannel.permission = 'settlement_bulk_update';
SettlementBulkChannel.title = 'Settlement Bulk Channel Update';

export default function SettlementBulkChannel() {
  return(
    <Form class="full-span full-elements" style={{ width: '400px' }}>
      <TextAreaField label="Settlement ID's" name="settlement_ids" placeholder="Please enter comma(,) seperated settlement id's"/>
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
          
          body.settlement_ids = body.settlement_ids ? body.settlement_ids.split(',') : [];

          return adminPut({
              route_name: 'setl_bulk',
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