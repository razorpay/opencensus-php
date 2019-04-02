import React from 'react';
import Form from 'ui/Form';
import { TextAreaField } from 'ui/Field';

import { adminPut } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

export default function EditGatewayUnprocessedRefundsConfig() {
  function onSubmit(body) {
    if (body.refund_ids) {
      let payload = {
        url: `live/config/keys`,
        data: {
         'config:GATEWAY_UNPROCESSED_REFUNDS': splitAndFilter(body.refund_ids, ','),
        },
      };

      adminPut(payload).then(response => {
        if (response) {
          notifySuccess(
            'Refunds have been added in unprocessed list successfully.'
          );
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
    } else {
      notifyError('Refund Ids are mandatory.');
    }
  }

  return (
    <Form
      class="full-span edit-gateway-unprocessed-refunds-config-action"
      onSubmit={onSubmit}
    >
      <TextAreaField
        label="Refund Ids"
        type="text"
        name="refund_ids"
        required
        placeholder="Enter comma separated refund ids"
      />
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Submit
        </button>
      </div>
    </Form>
  );
}

EditGatewayUnprocessedRefundsConfig.title =
  'Edit Gateway Unprocessed Refunds Config';
EditGatewayUnprocessedRefundsConfig.permission = 'retry_refund';
