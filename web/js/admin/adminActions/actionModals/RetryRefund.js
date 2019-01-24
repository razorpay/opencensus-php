import React from 'react';
import Form from 'ui/Form';
import Field, { SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal, notifyError } from 'common/modal';

RetryRefund.title = 'Retry Refund to Bank Account';
RetryRefund.permission = 'edit_payment_refund';

const options = {
  transfer_modes: ['IMPS', 'NEFT', 'RTGS', 'IFT'],
};

export default function RetryRefund() {
  return (
    <Form class="full-span">
      <Field label="Refund Id" type="text" name="id" required />
      <Field
        label="Beneficiary Name"
        type="text"
        name="beneficiary_name"
        required
      />
      <Field
        label="Account Number"
        type="text"
        name="account_number"
        required
      />
      <Field label="IFSC" type="text" name="ifsc_code" required />

      <div class="field multi">
        <label>Transfer Mode</label>
        <select name="transfer_mode">
          <option value="">Any</option>
          {options.transfer_modes.map((opt, idx) => (
            <option key={opt} value={opt}>
              {opt}
            </option>
          ))}
        </select>
      </div>

      <SelectMode defaultValue="live" />
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          adminPost({
            url: `${body.mode}/refunds/${body.id}/retry`,
            data: {
              bank_account: {
                account_number: body.account_number,
                ifsc_code: body.ifsc_code,
                beneficiary_name: body.beneficiary_name,
                transfer_mode: body.transfer_mode,
              },
            },
          }).then(response => {
            if (response) {
              notifySuccess('Refund successful');
              closeModal();
            } else {
              notifyError('Unexpected error happened');
            }
          });
        }}
      />
    </Form>
  );
}
