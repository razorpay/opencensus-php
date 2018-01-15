import React from 'react';
import Form from 'ui/Form';
import Field, { SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPut } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

EditBankTransferAccount.title = 'Edit Payer Account For Bank Transfer';
export default function EditBankTransferAccount() {
  return (
    <Form class="full-span">
      <Field label="Bank Transfer Id" type="text" name="id" required />
      <Field label="Beneficiary Name" type="text" name="beneficiary_name" />
      <Field label="Account Number" type="text" name="account_number" />
      <Field label="IFSC" type="text" name="ifsc_code" />
      <SelectMode defaultValue="live" />
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          return adminPut({
            route_name: 'bank_transfers_edit_payer_account',
            mode: body.mode,
            body: {
              beneficiary_name: body.beneficiary_name || undefined,
              account_number: body.account_number || undefined,
              ifsc_code: body.ifsc_code || undefined,
            },
            url_params: {
              id: body.id,
            },
          }).then(response => {
            if (response) {
              notifySuccess('Bank account edited successfully');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
