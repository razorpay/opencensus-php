import React from 'react';

import { adminPost } from 'common/fetch';
import { notifySuccess, notifyError, closeModal } from 'common/modal';

import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

export default ({ merchantId }) => {
  const handleSubmit = ({ submerchantId }) => {
    return adminPost({
      url: `live_${merchantId}/merchants/${submerchantId}/access_maps`,
      headers: { ['X-Razorpay-Account']: merchantId },
    }).then(response => {
      if (response) {
        notifySuccess('Sub merchant added successfully');
        closeModal();
      }
    });
  };

  return (
    <ModalContent header="Add Sub-Merchant">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <Field label="Merchant Id" name="submerchantId" />

        <AsyncButton
          text="Add"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
    </ModalContent>
  );
};
