import React from 'react';
import Form from 'ui/Form';
import { TextAreaField, SelectField } from 'ui/Field';

import { adminPut } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

export default function BulkEditMerchants() {
  function onSubmit(body) {
    if (!body.merchant_ids) {
      notifyError('Merchant Ids are mandatory.');
      return;
    }

    if (!body.attributes) {
      notifyError('Please select at least one field to update.');
      return;
    }

    Object.keys(body.attributes).forEach(key => {
      if (!body.attributes[key]) {
        delete body.attributes[key];
      }
    });

    let payload = {
      url: `live/merchants/bulk`,
      data: {
        merchant_ids: splitAndFilter(body.merchant_ids, ','),
        attributes: body.attributes,
      },
    };

    adminPut(payload).then(response => {
      if (response) {
        if (response.success) {
          notifySuccess(
            `${response.success} merchant(s) have been updated successfully.`
          );
        } else {
          notifyError(`Failed to update the merchants.`);
        }
        closeModal();
        openModal(
          <ModalContent header="API Response">
            <div class="code" style={{ width: '650px' }}>
              {JSON.stringify(response, null, 4)}}
            </div>
          </ModalContent>
        );
      }
    });
  }

  return (
    <Form class="full-span edit-merchants-action" onSubmit={onSubmit}>
      <TextAreaField
        label="Merchant Ids"
        type="text"
        name="merchant_ids"
        required
        placeholder="Enter comma separated merchant ids"
      />
      <SelectField name="attributes[hold_funds]" label="Hold Funds">
        <option value="" />
        <option value="1">Enable</option>
        <option value="0">Disable</option>
      </SelectField>
      <SelectField name="attributes[international]" label="International">
        <option value="" />
        <option value="1">Enable</option>
        <option value="0">Disable</option>
      </SelectField>
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Update
        </button>
      </div>
    </Form>
  );
}

BulkEditMerchants.title = 'Edit Merchants in Bulk';
BulkEditMerchants.permission = 'edit_bulk_merchant';
