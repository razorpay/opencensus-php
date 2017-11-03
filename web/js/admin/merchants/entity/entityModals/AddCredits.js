import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import Field, { SelectMode, SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

export default ({ props }) => {
  function onSubmit(body) {
    const mode = body.mode;
    delete body.mode;

    return adminPost({
      route_name: 'credits_create',
      mode,
      url_params: {
        id: props.merchant.details.id,
      },
      body,
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchant email updated successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Credits">
      <Form>
        <SelectMode />

        <SelectField label="Type" name="type" defaultValue="amount">
          <option value="amount" selected>
            Amount
          </option>
          <option value="fee">Fee</option>
        </SelectField>

        <Field label="Campaign" name="campaign" />
        <Field
          label="Amount(Paise)"
          name="value"
          type="number"
          infoMsg="This will add a amount credits of ( Paise) to the merchant amount credits balance."
        />

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </BaseModal>
  );
};
