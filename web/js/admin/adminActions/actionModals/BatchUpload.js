import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, FileField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminFormUpload } from 'common/fetch';
import { notifySuccess, notifyError, closeModal } from 'common/modal';
import { snakeToTitleCase } from 'common/util';

const options = {
  types: [
    'emandate',
    'payment_link',
    'refund',
    'irctc_refund',
    'irctc_settlement',
    'linked_account',
    'virtual_bank_account',
    'bank_transfer',
    'recurring_charge',
    'reconciliation',
    'payout',
    'sub_merchant',
    'direct_debit',
  ],
  subTypes: {
    emandate: ['register', 'debit', 'acknowledge'],
  },
};

export default class BatchUpload extends Component {
  static permission = 'admin_batch_create';
  static title = 'Batch Upload';

  //populate emandate details first
  state = {
    type: options.types[0],
    subTypes: options.subTypes.emandate,
  };

  handleTypeChange = e => {
    const type = e.target.value;
    let subTypes = options['subTypes'][type] || null;

    this.setState({ subTypes, type });
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
    const { type, subTypes } = this.state;

    return (
      <Form class="full-span full-elements" style={{ width: '650px' }}>
        <Field label="Merchant ID" name="merchant_id" required={true} />
        <FileField label="Batch File" name="file" required={true} />
        <SelectField
          label="Type"
          name="type"
          onChange={this.handleTypeChange}
          valud={type}
        >
          {options.types.map(type => (
            <option value={type} key={type}>
              {snakeToTitleCase(type)}
            </option>
          ))}
        </SelectField>

        {subTypes && (
          <SelectField label="Sub Type" name="sub_type">
            {subTypes.map(subType => (
              <option value={subType} key={subType}>
                {snakeToTitleCase(subType)}
              </option>
            ))}
          </SelectField>
        )}

        {/* conditionally load extra fields according to batch types */}

        {['reconciliation', 'emandate', 'virtual_bank_account'].indexOf(type) <
        0 ? (
          <Field label="File Name" name="name" />
        ) : null}

        {['reconciliation', 'emandate'].indexOf(type) > -1 ? (
          <Field label="Gateway" name="gateway" />
        ) : null}

        {['reconciliation', 'payment_link'].indexOf(type) > -1 ? (
          <TextAreaField label="Config" name="config" />
        ) : null}

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
