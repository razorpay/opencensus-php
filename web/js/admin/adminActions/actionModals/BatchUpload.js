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
    gateways: ['enach_rbl', 'hdfc', 'axis'],
  },
  reconciliation: {
    extraFields: ['name', 'config', 'gateway'],
    gateways: ['enach_rbl', 'hdfc', 'axis'],
  },
};

export default class BatchUpload extends Component {
  static permission = 'admin_batch_create';
  static title = 'Admin Batch Upload';

  //populate emandate details first
  state = {
    selectedType: 'emandate',
  };

  handleTypeChange = e => {
    const selectedType = e.target.value;

    this.setState({ selectedType });
  };

  handleSave = body => {
    let form = {
      file: document.querySelector('[name=file]').files[0],
    };

    if (!form.file) {
      return notifyError('Please select a file');
    }

    delete body.merchant_id;

    form = { ...body, ...form };

    return adminFormUpload(form, `/admin/api/live/admin/batches`).then(
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
    const extraFields = options[selectedType].extraFields || [],
      gateways = options[selectedType].gateways || [];

    return (
      <Form class="full-span full-elements" style={{ width: '650px' }}>
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

        {extraFields.indexOf('gateway') > -1 && (
          <SelectField label="Gateway" name="gateway">
            {gateways.map(gateway => (
              <option value={gateway} key={gateway}>
                {snakeToTitleCase(gateway)}
              </option>
            ))}
          </SelectField>
        )}

        {extraFields.indexOf('name') > -1 && (
          <Field label="File Name" name="name" defaultValue="file" />
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
