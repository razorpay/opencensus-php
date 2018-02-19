import React from 'react';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';
import { isWorkflow } from 'common/util';
import { adminPut } from 'common/fetch';

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
            url: `${data.mode}/merchants/${data.merchantId}/invoice/gstin`,
            data: { invoice_number: data.invoiceNumber },
          }).then(response => {
            if (response) {
              closeModal();

              if (isWorkflow(response)) {
                notifySuccess('Workflow is created successfully.');
                return;
              }
              notifySuccess('Update GSTIN Successfull');
            }
          });
        }}
      />
    </Form>
  );
}
