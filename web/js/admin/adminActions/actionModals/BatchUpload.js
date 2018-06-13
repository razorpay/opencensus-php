import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, FileField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminFormUpload } from 'common/fetch';
import { notifySuccess, notifyError, closeModal } from 'common/modal';
import { snakeToTitleCase } from 'common/util';

const options = {
  emandate: {
    subTypes: ['register', 'debit', 'acknowledge'],
    extraFields: ['name', 'gateway'],
  },
  payment_link: {
    extraFields: ['config'],
  },
  reconciliation: {
    extraFields: ['name', 'config', 'gateway'],
  },
  virtual_bank_account: {
    extraFields: ['name'],
  },
  refund: {},
  irctc_refund: {},
  irctc_settlement: {},
  linked_account: {},
  bank_transfer: {},
  recurring_charge: {},
  payout: {},
  sub_merchant: {},
  direct_debit: {},
};

export default class BatchUpload extends Component {
  static permission = 'admin_batch_create';
  static title = 'Batch Upload';

  //populate emandate details first
  state = {
    selectedType: 'emandate',
  };

  handleTypeChange = e => {
    const selectedType = e.target.value;

    this.setState({ selectedType });
  };

  handleSave = body => {
    const merchantId = body.merchant_id;
    let form = {
      file: document.querySelector('[name=file]').files[0],
    };

    if (!merchantId) {
      return notifyError('Please enter the merchant id');
    }

    if (!form.file) {
      return notifyError('Please select a file');
    }

    delete body.merchant_id;

    form = { ...body, ...form };

    return adminFormUpload(form, `/admin/api/live_${merchantId}/batches`).then(
      response => {
        if (response.data.success) {
          notifySuccess('Batch uploaded successfully!');
          closeModal();
        } else {
          notifyError(response.data.errors[0]);
        }
      }
    );
  };

  render() {
    const { selectedType } = this.state;
    const extraFields = options[selectedType].extraFields || [];

    return (
      <Form class="full-span full-elements" style={{ width: '650px' }}>
        <Field label="Merchant ID" name="merchant_id" required={true} />
        <FileField label="Batch File" name="file" required={true} />
        <SelectField
          label="Type"
          name="type"
          onChange={this.handleTypeChange}
          value={selectedType}
        >
          {Object.keys(options).map(type => (
            <option value={type} key={type}>
              {options[type]['label'] || snakeToTitleCase(type)}
            </option>
          ))}
        </SelectField>

        {options[selectedType].subTypes && (
          <SelectField label="Sub Type" name="sub_type">
            {options[selectedType].subTypes.map(subType => (
              <option value={subType} key={subType}>
                {snakeToTitleCase(subType)}
              </option>
            ))}
          </SelectField>
        )}

        {/* conditionally load extra fields according to batch types */}
        {extraFields.indexOf('name') > -1 && (
          <Field label="File Name" name="name" />
        )}

        {extraFields.indexOf('gateway') > -1 && (
          <Field label="Gateway" name="gateway" />
        )}

        {extraFields.indexOf('config') > -1 && (
          <TextAreaField label="Config" name="config" />
        )}

        <AsyncButton
          text="Upload"
          class="btn"
          pendingClass="small spinner"
          onSubmit={this.handleSave}
        />
      </Form>
    );
  }
}
