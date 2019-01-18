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

import { priorityRefunds } from 'admin/scrooge/util';

PriorityRefunds.title = 'Priority Refunds';
PriorityRefunds.permission = 'edit_refund';

export default function PriorityRefunds() {
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
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={pushRefunds}
      />
    </Form>
  );
}

const pushRefunds = data => {
  const { ids, mode } = data;

  if (ids && mode) {
    const selectedRefunds = splitAndFilter(ids, ',');

    priorityRefunds(selectedRefunds, mode);
  } else {
    notifyError('Required fields are empty');
  }
};
