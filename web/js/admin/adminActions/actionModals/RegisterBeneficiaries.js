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

export default function RegisterBeneficiaries() {
  function onSubmit(body) {
    if (body.merchant_ids) {
      let merchantIds = splitAndFilter(body.merchant_ids, ',');

      if (merchantIds.length > 1000) {
        notifyError('Number of merchant ids should not be more than 1000.');
        return;
      }

      let payload = {
        url: `live/merchants/beneficiary/file/${body.bene_channel}`,
        data: {
          merchant_ids: merchantIds,
        },
      };

      adminPost(payload).then(response => {
        if (response) {
          notifySuccess('Beneficiaries have been registered successfully');
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
    } else {
      notifyError('Merchant Ids are mandatory.');
    }
  }

  return (
    <Form class="full-span register-beneficiaries-action" onSubmit={onSubmit}>
      <Field required label="Channel" type="text" name="bene_channel" />
      <TextAreaField
        label="Merchant Ids"
        type="text"
        name="merchant_ids"
        required
        placeholder="Enter comma separated merchant ids"
        helpMsg="Supports only upto 1000 merchant Ids at one time."
      />
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Submit
        </button>
      </div>
    </Form>
  );
}

RegisterBeneficiaries.title = 'Register Beneficiaries';
