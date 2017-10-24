import React from 'react';
import { TextAreaField } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

RetryFailedSettlements.title = 'Retry Failed Settlements';
export default function RetryFailedSettlements() {
  return (
    <div>
      <header>{RetryFailedSettlements.title}</header>
      <Form>
        <TextAreaField
          label="Settlement IDs:"
          placeholder="Please enter comma separated Settlement IDs"
          name="settlement_ids"
        />
        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={data => {
            let body = {
              settlement_ids: data.settlement_ids
                ? data.settlement_ids.split(',')
                : [],
            };
            return adminPost({
              body,
              route_name: 'setl_retry',
            })
              .then(response => {
                if (response.data.success) {
                  notifySuccess(response.data.data.message);
                  closeModal();
                } else {
                  response.data.errors.map(error => notifyError(error));
                }
              })
              .catch(err => {
                notifyError(JSON.stringify(err.response));
              });
          }}
        />
      </Form>
    </div>
  );
}
