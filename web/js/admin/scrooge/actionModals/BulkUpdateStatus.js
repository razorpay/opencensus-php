import React from 'react';
import Form from 'ui/Form';
import { SelectField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { splitAndFilter, snakeToTitleCase } from 'common/util';
import { ModalContent } from 'component/Modal';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';

import { bulkUpdateRefundsStatus } from 'admin/scrooge/util';
import { updateStatusEvents } from 'admin/scrooge/constants';

BulkUpdateStatus.title = 'Bulk Update Status';
BulkUpdateStatus.permission = 'edit_refund';

export default function BulkUpdateStatus() {
  return (
    <Form class="full-span bulk-verify-payments">
      <SelectField label="Mode" name="mode" required>
        <option value="live">Live</option>
        <option value="test">Test</option>
      </SelectField>
      <TextAreaField
        label="Refund Ids"
        type="text"
        name="ids"
        required
        placeholder="Enter comma separated refund ids"
      />
      <SelectField label="Status to update" name="event" required>
        <option key="0" value="">
          Select status to update
        </option>
        {updateStatusEvents.map((e, i) => (
          <option key={i + 1} value={e}>
            {e}
          </option>
        ))}
      </SelectField>
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={updateStatus}
      />
    </Form>
  );
}

const updateStatus = data => {
  const { ids, mode, event } = data;

  if (ids && mode && event) {
    const selectedRefunds = splitAndFilter(ids, ',');

    bulkUpdateRefundsStatus(selectedRefunds, event, mode);
  } else {
    notifyError('Required fields are empty');
  }
};
