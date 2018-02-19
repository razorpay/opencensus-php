import React from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPut } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

ConfirmUser.permission = 'edit_merchant_confirm';
ConfirmUser.title = 'Confirm User';
export default function ConfirmUser() {
  return (
    <Form class="full-span">
      <Field label="User Email" type="email" name="email" />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          return adminPut({
            url: 'live/users/confirm_user_by_data',
            data: body,
          }).then(response => {
            if (response) {
              notifySuccess('User confirmed successfully.');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
