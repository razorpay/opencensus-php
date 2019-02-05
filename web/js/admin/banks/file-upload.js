import { Component } from 'react';

import Form from 'ui/Form';
import Field, { FileField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminFormUpload } from 'common/fetch';
import { notifyError, notifySuccess } from 'common/modal';

export default class BankFileUpload extends Component {
  handleSubmit = body => {
    const $fileInput = document.querySelector('[name=file]');
    let form = {
      file: $fileInput.files[0],
    };

    if (!form.file) {
      return notifyError('Please select a file');
    }

    return adminFormUpload(
      form,
      `/admin/api/live/admin/files/${body.type}`
    ).then(response => {
      if (response.data.success) {
        notifySuccess('Batch uploaded successfully!');
        $fileInput.value = '';
      } else {
        notifyError(response.data.errors[0]);
      }
    });
  };

  render() {
    return (
      <div class="box">
        <header>Upload Dispute File</header>
        <Form class="full-span full-elements" style={{ width: '400px' }}>
          <Field
            label="Select File type"
            name="type"
            value="dispute"
            disabled
          />
          <FileField label="Upload Dispute File" name="file" />
          <AsyncButton
            text="Upload"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleSubmit}
          />
        </Form>
      </div>
    );
  }
}
