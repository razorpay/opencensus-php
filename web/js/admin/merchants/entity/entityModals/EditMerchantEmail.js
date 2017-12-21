import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'common/fetch';

export default ({ merchantId }) => {
  function onSubmit(body) {
    return adminPut(
      {
        email: body.email,
      },
      '/admin/merchant/' + merchantId + '/email'
    )
      .then(response => {
        if (response) {
          notifySuccess('Merchant email updated successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Edit Merchant Email">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <Field label="Email" name="email" type="email" />

        <div class="m-t m-b info-block text-danger">
          <strong>
            Warning: You need to be a superadmin in order to edit merchant email
            address.
          </strong>
        </div>

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </BaseModal>
  );
};
