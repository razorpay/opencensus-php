import React from 'react';
import Form from 'ui/Form';
import { TextAreaField } from 'ui/Field';

import { adminPost } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

export default function RetryBulkRefunds() {
  function onSubmit(body) {
    if (body.refund_ids) {
      let payload = {
        url: `live/refunds/retry/bulk`,
        data: {
          refund_ids: splitAndFilter(body.refund_ids, ','),
        },
      };

      adminPost(payload).then(response => {
        if (response) {
          notifySuccess('Refund retry has been successfully initiated.');
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
    <Form class="full-span retry-bulk-refunds-action" onSubmit={onSubmit}>
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

RetryBulkRefunds.title = 'Retry Refunds in Bulk';
RetryBulkRefunds.permission = 'retry_refund';
