import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import { TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'common/fetch';

export default ({ props, merchantId }) => {
  function onSubmit(body) {
    return adminPut({
      url: `live/merchant/activation/${merchantId}/update`,
      data: {
        comment: body.comment,
      },
    })
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
      <Form class="full-span full-elements">
        <TextAreaField
          label="Comment"
          name="comment"
          defaultValue={props.merchant.details.comment}
        />

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
