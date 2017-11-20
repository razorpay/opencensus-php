import React from 'react';
import { withRouter } from 'react-router-dom';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';
import { isWorkflow } from 'util/index';
import { adminPut } from 'util/fetch';

const EditMerchantInvoice = withRouter(({ history }) => {
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
              if (isWorkflow(response, this.props.history)) {
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
});

EditMerchantInvoice.title = 'Edit Merchant Invoice GSTIN';

export default EditMerchantInvoice;
