import React from 'react';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';
import { isWorkflow } from 'util/index';
import { adminPut } from 'util/fetch';

EditMerchantInvoice.title = 'Edit Merchant Invoice GSTIN';
EditMerchantInvoice.permission = 'edit_merchant_invoice_gstin';
export default function EditMerchantInvoice() {
  return (
    <Form>
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
      <br />
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
              if (isWorkflow(response)) {
                return;
              }
              notifySuccess('Update GSTIN Successfull');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
