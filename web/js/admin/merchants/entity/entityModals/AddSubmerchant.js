import React from 'react';
import { ModalContent } from 'component/modal';

import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

export default () => {
  const handleSubmit = () => {
    // TODO: handle submit add sub-merchant form
  };
  return (
    <ModalContent header="Add Sub-Merchant">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <Field label="Merchant Id" name="merchant_id" />

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
