import React from 'react';
import { ModalContent } from 'component/Modal';

import Form from 'ui/Form';
import Field, { SelectMode, SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost, adminPut } from 'common/fetch';
import { isWorkflow } from 'common/util';

export default ({ merchantId, model = {}, opts = {} }) => {
  function OnSuccess(response) {
    if (response) {
      closeModal();

      if (isWorkflow(response)) {
        notifySuccess('Workflow is created successfully.');
        return;
      }
      // Execute success handler if provided by parent component
      if (opts.successHandler) {
        opts.successHandler(response);
      }
      notifySuccess('Credits updated successfully.');
    }
  }

  function onSubmit(body) {
    const mode = body.mode;
    const requestFunc = model.id ? adminPut : adminPost,
      requestUrl = model.id
        ? `${mode}/merchants/${merchantId}/credits/${model.id}`
        : `${mode}/merchants/${merchantId}/credits_log`;

    if (model.id) {
      body = { value: body.value };
    } else {
      delete body.mode;
    }

    return requestFunc({
      url: requestUrl,
      data: body,
    })
      .then(OnSuccess)
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <ModalContent header={`${model.id ? 'Edit' : 'Add'} Credits`}>
      <Form class="full-span" style={{ width: '350px' }}>
        <SelectMode disabled={!!model.id} defaultValue={model.mode} />

        <SelectField
          label="Type"
          name="type"
          defaultValue={model.type}
          disabled={!!model.id}
        >
          <option value="amount">Amount</option>
          <option value="fee">Fee</option>
          <option value="refund">Refund</option>
        </SelectField>

        <Field
          label="Campaign"
          name="campaign"
          disabled={!!model.id}
          defaultValue={model.campaign}
        />
        <Field
          label="Amount(Paise)"
          name="value"
          type="number"
          infoMsg="This will add a amount credits of ( Paise) to the merchant amount credits balance."
          defaultValue={model.value}
        />

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </ModalContent>
  );
};
