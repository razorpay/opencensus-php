import React, { Component } from 'react';

import Field, { DateField, TextAreaField } from 'ui/Field';
import Table from 'ui/Table';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';

import { notifySuccess, notifyError, closeModal } from 'common/modal';
import { adminPost } from 'common/fetch';
import { isBlank } from 'common/util';

export default class MerchantInvoiceNote extends Component {
  static title = 'Merchant Invoice Notes (credit/debit)';
  static permission = 'merchant_invoice_edit';

  state = {
    invoice_entities: [],
  };

  fields = [
    ['Merchant ID', item => item.merchant_id],
    ['Amount', item => item.amount],
    ['Tax', item => item.tax],
    ['Month', item => item.month],
    ['year', item => item.year],
    ['Description', item => item.description],
  ];

  handleAdd = body => {
    if (!isBlank(body)) {
      let invoice_entities = [...this.state.invoice_entities];
      let month, year;

      month = new Date(body.month_year).getMonth() + 1;

      year = body.month_year.split('/')[1];

      delete body.month_year;

      invoice_entities.push({ ...body, month, year });

      this.setState({ invoice_entities });
    } else {
      notifyError('Please fill the form to proceed');
    }
  };

  handleSave = _ => {
    const { invoice_entities } = this.state;

    if (invoice_entities.length) {
      return adminPost({
        url: 'live/merchants/invoice/bulk',
        data: { invoice_entities },
      }).then(response => {
        if (response) {
          notifySuccess('Invoice notes saved successfully!');
        }
      });
    } else {
      return notifyError('Please atleast add one note.');
    }
  };

  render() {
    return (
      <Form>
        <Field name="merchant_id" label="Merchant ID" required={true} />

        <Field name="amount" label="Amount" required={true} />

        <Field name="tax" label="Tax" required={true} />

        <DateField
          format="MMM/YYYY"
          name="month_year"
          label="Month/Year"
          required={true}
          type="month"
        />

        <Field name="description" label="Description" required={true} />

        <AsyncButton
          text="+ Add"
          class="btn btn-default"
          pendingClass="small spinner"
          onSubmit={this.handleAdd}
        />

        <AsyncButton
          text="Save"
          class="btn"
          pendingClass="small spinner"
          disabled={!this.state.invoice_entities.length > 0}
          onSubmit={this.handleSave}
        />

        <Table
          animateRow={false}
          items={this.state.invoice_entities}
          fields={this.fields}
        />
      </Form>
    );
  }
}
