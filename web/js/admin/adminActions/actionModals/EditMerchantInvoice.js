import React from 'react';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'util/fetch';

EditMerchantInvoice.title = 'Edit Merchant Invoice GSTIN';
export default function EditMerchantInvoice() {
  return (
    <Form>
      <header>{EditMerchantInvoice.title}</header>
      <Field
        label="Merchant ID"
        placeholder="Enter Merchant ID"
        name="merchantId"
        type="text"
      />
      <Field
        label="Invoice Number"
        placeholder="Enter Invoice Number"
        type="text"
        name="invoiceNumber"
      />
      <SelectMode />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          return adminPut({
            url_params: {
              id: data.merchantId,
            },
            body: { invoice_number: data.invoiceNumber },
            mode: data.mode,
            route_name: 'merchant_invoice_update_gstin',
          }).then(response => {
            if (response) {
              notifySuccess('Update GSTIN Successfull');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
