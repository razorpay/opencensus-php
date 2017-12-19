import React from 'react';
import { TextAreaField } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

RetryFailedSettlements.permission = 'retry_settlement';
RetryFailedSettlements.title = 'Retry Failed Settlements';
export default function RetryFailedSettlements() {
  return (
    <div>
      <Form class="full-span full-elements" style={{ width: '400px' }}>
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
              console.log('retry settlement', response);
              if (response) {
                let message = '';

                for (const key in response) {
                  if (response.hasOwnProperty(key) && response.message) {
                    message += response.message + '\n';
                  }
                }

                notifySuccess(message);
                closeModal();
              }
            });
          }}
        />
      </Form>
    </div>
  );
}
