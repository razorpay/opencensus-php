import React from 'react';
import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';

import { adminPost } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';
import { adminPut } from 'common/fetch';

export default function EditBankAccount() {
  function onSubmit(body) {
    let payload = {
      url: `live/bank_accounts/${body.ba_id}`,
      data: {
        beneficiary_name: body.name,
      },
    };

    adminPut(payload).then(response => {
      if (response) {
        notifySuccess('Bank account has been edited successfully');
        closeModal();
        openModal(
          <ModalContent header="API Response" noPadding>
            <div class="code" style={{ width: '650px' }}>
              {JSON.stringify(response, null, 4)}}
            </div>
          </ModalContent>
        );
      }
    });
  }

  return (
    <Form class="full-span edit-bank-account-action" onSubmit={onSubmit}>
      <Field required label="Beneficiary Name" type="text" name="name" />
      <Field required label="Bank Account ID" type="text" name="ba_id" />
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Submit
        </button>
      </div>
    </Form>
  );
}

EditBankAccount.title = 'Edit Bank Account';
EditBankAccount.permission = 'edit_merchant_bank_detail';
