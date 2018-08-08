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

export default function ProcessBulkRefunds() {
  function onSubmit(body) {
    if (body.refund_ids) {
      let payload = {
        url: `live/refunds/status/processed`,
        data: {
          refund_ids: splitAndFilter(body.refund_ids, ','),
        },
      };

      adminPut(payload).then(response => {
        if (response) {
          notifySuccess('Refund has been successfully processed.');
          closeModal();
          openModal(
            <ModalContent header="API Response:" noPadding>
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
    <Form class="full-span process-bulk-refunds-action" onSubmit={onSubmit}>
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

ProcessBulkRefunds.title = 'Mark Refunds as Processed in Bulk';
ProcessBulkRefunds.permission = 'edit_refund';
