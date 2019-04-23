import React from 'react';
import Form from 'ui/Form';
import { SelectField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { splitAndFilter } from 'common/util';
import { notifyError } from 'common/modal';

import { bulkUpdateRefundsReference1 } from 'admin/scrooge/util';

BulkUpdateReference1.title = 'Bulk Update Reference1';
BulkUpdateReference1.permission = 'update_scrooge_refund_reference1';

export default function BulkUpdateReference1() {
  return (
    <Form class="full-span bulk-verify-payments">
      <SelectField label="Mode" name="mode" required>
        <option value="live">Live</option>
        <option value="test">Test</option>
      </SelectField>
      <TextAreaField
        label="Refund Ids and Reference1"
        type="text"
        name="ids"
        required
        placeholder="Enter comma separated refund id : Reference1"
      />
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={bulkUpdate}
      />
    </Form>
  );
}

const bulkUpdate = data => {
  const { ids, mode } = data;

  if (ids) {
    const selectedRefunds = splitAndFilter(ids, ',');

    bulkUpdateRefundsReference1(selectedRefunds, mode);
  } else {
    notifyError('Refund Ids and Reference1 field is empty');
  }
};
