import React from 'react';
import { TextAreaField } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

RetryFailedSettlements.title = 'Retry Failed Settlements';
export default function RetryFailedSettlements() {
  return (
    <div>
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
            }).then(response => {
              if (response) {
                notifySuccess(response.message);
                closeModal();
              }
            });
          }}
        />
      </Form>
    </div>
  );
}
