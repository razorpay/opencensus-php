import React from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import { SelectField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { splitAndFilter, snakeToTitleCase } from 'common/util';
import { ModalContent } from 'component/Modal';
import {
  closeModal,
  notifyError,
  openModal,
  notifySuccess,
} from 'common/modal';

BulkVerifyRefunds.title = 'Bulk Verify Refunds';
export default function BulkVerifyRefunds() {
  return (
    <Form class="full-span bulk-verify-refunds">
      <TextAreaField
        label="Refund Ids with Attempts (no spaces allowed)"
        type="text"
        name="ids"
        required
        placeholder="Enter comma separated refund ids"
      />
      <SelectField label="Mode" name="mode" required>
        <option value="live">Live</option>
        <option value="test">Test</option>
      </SelectField>
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={({ ids, mode }) => {
          if (ids) {
            adminPost({
              url: `${mode}/refunds/verify/bulk`,
              data: {
                refund_data: splitAndFilter(ids, ','),
              },
            }).then(response => {
              if (response.result.length) {
                notifySuccess(response.msg);
                closeModal();
                openModal(
                  <ModalContent header="Bulk Verify Response" noPadding>
                    <Table items={response.result} fields={fields} />
                  </ModalContent>
                );
              } else {
                notifyError(response.msg);
              }
            });
          } else {
            notifyError('Refund Ids field is mandatory');
          }
        }}
      />
    </Form>
  );
}

const fields = [
  ['Refund ID', item => 'rfnd_' + item.refund_id],
  ['Attempt Number', item => item.attempt_number],
  ['Success', item => item.success],
  ['Payment ID', item => 'pay_' + item.payment_id],
  ['Verify Response', item => item.verify_response],
];
