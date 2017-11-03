import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import { TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'util/fetch';

export default ({ props }) => {
  function onSubmit(body) {
    const data = {
      route_name: 'merchant_activation_update',
      url_params: {
        id: props.merchant.details.id,
      },
      body: {
        comment: body.comment,
      },
    };

    return adminPut(data)
      .then(response => {
        if (response) {
          notifySuccess('Comment updated successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Edit Comment">
      <Form>
        <TextAreaField label="Comment" name="comment" />

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
