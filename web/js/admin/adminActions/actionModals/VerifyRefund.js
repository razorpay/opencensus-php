import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { ModalContent } from 'component/Modal';
import { adminFetch } from 'common/fetch';
import { notifySuccess, closeModal, openModal } from 'common/modal';

VerifyRefund.title = 'Verify Refund';
export default function VerifyRefund() {
  return (
    <Form class="full-span">
      <Field label="Refund ID (with sign)" type="text" name="id" required />
      <SelectField label="Mode" name="mode" required>
        <option value="live">Live</option>
        <option value="test">Test</option>
      </SelectField>
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={({ id, mode }) =>
          adminFetch(`${mode}/refunds/${id}/verify`).then(response => {
            if (response) {
              notifySuccess('Refund Verified successfully');
              closeModal();
              openModal(
                <ModalContent header="API Response" noPadding>
                  <div class="code" style={{ width: '650px' }}>
                    {JSON.stringify(response, null, 4)}}
                  </div>
                </ModalContent>
              );
            }
          })
        }
      />
    </Form>
  );
}
