import React from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminUserConfirm } from 'util/fetch';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

ConfirmUser.title = 'Confirm User';
export default function ConfirmUser() {
  return (
    <Form>
      <header>{ConfirmUser.title}</header>
      <Field label="User Email" type="email" name="email" />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={data => {
          data.email = data.email || '';
          return adminUserConfirm(data)
            .then(response => {
              if (response.data.success) {
                notifySuccess('User confirmed successfully.');
                closeModal();
              } else {
                response.data.errors.map(error => notifyError(error));
              }
            })
            .catch(err => {
              notifyError(JSON.stringify(err.response.statusText));
            });
        }}
      />
    </Form>
  );
}
