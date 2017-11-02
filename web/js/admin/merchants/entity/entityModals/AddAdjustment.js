import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, confirm, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

export default ({ props }) => {
  function handleConfirm(body) {
    return confirm(
      'Adjustment once assigned can not be changed, ensure you have checked all values.',
      () => {
        closeModal();

        const mode = body.mode;
        delete body.mode;

        return adminPost({
          route_name: 'adj_add',
          merchant_id: props.details.id,
          mode,
          body,
        })
          .then(response => {
            if (response) {
              notifySuccess('Adjustment added successfully.');
              closeModal();
            }
          })
          .catch(err => {
            notifyError(JSON.stringify(err.response));
          });
      },
      'Submit',
      'Cancel'
    );
  }

  return (
    <BaseModal header="Add Adjustment">
      <span>
        <strong>
          Warning: The adjustment once assigned can not be changed.
        </strong>
      </span>

      <Form>
        <Field
          label="Amount (in Paise)"
          name="amount"
          type="number"
          placeholder="Amount in Paise (INR 1 as 100)"
        />
        <Field label="Currency" name="currency" />
        <Field label="Description" name="description" />
        <Field label="Settlement Id" name="settlement_id" />

        <SelectField label="Mode" name="mode" defaultValue="live">
          <option value="test">Test</option>
          <option value="live">Live</option>
        </SelectField>

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleConfirm}
        />
      </Form>
    </BaseModal>
  );
};
