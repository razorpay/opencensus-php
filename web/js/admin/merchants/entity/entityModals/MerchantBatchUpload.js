import React from 'react';
import BaseModal from 'ui/BaseModal';

import { notifyError, notifySuccess, closeModal } from 'common/modal';
import { adminFormUpload } from 'util/fetch';

import Form from 'ui/Form';
import { FileField, SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

export default ({ merchantId }) => {
  function handleSubmit(body) {
    let requestData = {};

    requestData.mode = body.mode;
    delete body.mode;

    if (!Object.keys(body).length) {
      notifyError('Please upload atleast 1 file');

      return false;
    }

    if (body.refund) {
      requestData['file[data][refund]'] = body.refund[0];
    }

    if (body.settlement) {
      requestData['file[data][settlement]'] = body.settlement[0];
    }

    return adminFormUpload({
      route_name: 'merchant_batches',
      'body[type]': 'irctc',

      url_params: JSON.stringify({
        '{id}': merchantId,
      }),
      ...requestData,
    })
      .then(response => {
        if (response.data.success) {
          notifySuccess('Uploaded successfully.');
          closeModal();
        } else {
          response.data.errors.map(error => notifyError(error));
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Batch Upload">
      <Form class="full-span full-elements" style={{ width: '400px' }}>
        {entitiesList.map(entity => (
          <FileField
            key={entity.name}
            label={entity.label}
            accept="text/plain"
            name={entity.name}
          />
        ))}
        <SelectMode defaultValue="live" />

        <AsyncButton
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
    </BaseModal>
  );
};

/* Resources */
const entitiesList = [
  { label: 'Refund File', name: 'refund' },
  { label: 'Settlement File', name: 'settlement' },
];
