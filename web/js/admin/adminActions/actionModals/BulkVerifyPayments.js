import React from 'react';
import Form from 'ui/Form';
import { SelectField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { splitAndFilter, snakeToTitleCase } from 'common/util';
import { ModalContent } from 'component/Modal';
import Table from 'ui/Table';
import { closeModal, notifyError, openModal } from 'common/modal';

BulkVerifyPayments.title = 'Bulk Verify Payments';
export default function BulkVerifyPayments() {
  return (
    <Form class="full-span bulk-verify-payments">
      <TextAreaField
        label="Payment Ids"
        type="text"
        name="ids"
        required
        placeholder="Enter comma separated payment ids"
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
              url: `${mode}/payments/verify/bulk`,
              data: {
                payment_ids: splitAndFilter(ids, ','),
              },
            }).then(response => {
              if (response) {
                // Rendering the response object in the modal as a table
                let fields = [
                  ['Field', item => item[0]],
                  ['Value', item => item[1]],
                ];
                let items = Object.keys(response)
                  .reverse()
                  .map(key => {
                    let val = response[key] && response[key].toString();
                    return [snakeToTitleCase(key), val];
                  });

                closeModal();
                openModal(
                  <ModalContent header="Bulk Payment Verification Response">
                    <div class="bulk-response-modal">
                      <Table items={items} fields={fields} />
                    </div>
                  </ModalContent>
                );
              }
            });
          } else {
            notifyError('Payment Ids field is mandatory.');
          }
        }}
      />
    </Form>
  );
}
