import React from 'react';
import Form from 'ui/Form';

import { adminFetch } from 'common/fetch';

import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

export default function FetchGatewayUnprocessedRefundsConfig() {
  function onSubmit(body) {
    let payload = {
      url: `live/config/key?key=config:GATEWAY_UNPROCESSED_REFUNDS`,
    };

    adminFetch(payload).then(response => {
      if (response) {
        notifySuccess(
          'Fetching refunds present in gateway unprocessed list is successful'
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
  }

  return (
    <Form
      class="full-span edit-gateway-unprocessed-refunds-config-action"
      onSubmit={onSubmit}
    >
      <div class="form-actions text-center">
        <button class="btn" type="submit">
          Submit
        </button>
      </div>
    </Form>
  );
}

FetchGatewayUnprocessedRefundsConfig.title =
  'Fetch Gateway Unprocessed Refunds Config';
FetchGatewayUnprocessedRefundsConfig.permission = 'retry_refund';
