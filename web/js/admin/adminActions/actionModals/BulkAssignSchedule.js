import React from 'react';
import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';

import { adminPost } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';
import { adminPut } from 'common/fetch';

export default function BulkAssignSchedule() {
  function onSubmit(body) {
    let payload = {
      url: `live/merchants/schedules/bulk`,
      data: {
        schedule: {
          type: body.schedule_type,
          schedule_id: body.schedule_id,
        },
        merchant_ids: splitAndFilter(body.merchantIds, ','),
      },
    };

    adminPost(payload).then(response => {
      if (response) {
        notifySuccess('Schedule assignment completed successfully');
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
    <Form class="full-span bulk-assign-schedule-action" onSubmit={onSubmit}>
      <Field required label="Schedule ID" type="text" name="schedule_id" />
      <Field
        required
        label="Schedule Type"
        type="text"
        value="settlement"
        name="schedule_type"
      />
      <TextAreaField
        label="Merchant Ids"
        type="text"
        name="merchantIds"
        required
        placeholder="Enter comma separated merchant IDs"
        class="merchant-ids"
      />
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Submit
        </button>
      </div>
    </Form>
  );
}

BulkAssignSchedule.title = 'Assign Merchant Schedules - Bulk';
BulkAssignSchedule.permission = 'schedule_assign_bulk';
