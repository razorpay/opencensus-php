import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'common/fetch';

export default ({ merchantId }) => {
  /* Submit button action */
  function onSubmit(body) {
    return adminPut({
      route_name: 'merchant_edit_config',
      merchant_id: merchantId,
      body: {
        handle: body.handle,
      },
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchant handle updated successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Assign Merchant Handle">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <Field label="Merchant Handle" name="handle" placeholder="XXXX" />

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
