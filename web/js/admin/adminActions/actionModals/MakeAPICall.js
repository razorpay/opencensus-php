import React, { Component } from 'react';
import Field, {
  SelectField,
  SelectMode,
  TextAreaField,
  FileField,
} from 'ui/Field';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import {
  notifySuccess,
  openModal,
  closeModal,
  notifyError,
} from 'common/modal';

import { adminFormUpload } from 'util/fetch';

export default class MakeAPICall extends Component {
  constructor() {
    super();
    this.state = {
      auth: 'admin',
      method: 'GET',
      file: '',
    };
  }

  handleChange = (e, code) => {
    this.setState({
      [code]: e.target.value,
    });
  };

  render() {
    let { auth, method, file } = this.state;
    return (
      <div class="makeapicall">
        <Form class="full-span" style={{ width: '650px' }}>
          <div class="field url">
            <label>URL</label>
            <span class="base-url">https://api.razorpay.com/v1/</span>
            <input name="url" placeholder="relative/url" />
          </div>
          <SelectMode />
          <SelectField
            label="Request Method"
            name="method"
            value={method}
            onChange={e => this.handleChange(e, 'method')}
          >
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="DELETE">DELETE</option>
            <option value="PATCH">PATCH</option>
          </SelectField>
          <SelectField
            label="AUTH"
            name="auth"
            onChange={e => this.handleChange(e, 'auth')}
            value={auth}
          >
            <option value="admin">Admin</option>
            <option value="proxy">Merchant</option>
            <option value="internal">Internal</option>
          </SelectField>

          {this.state.auth === 'proxy' ? (
            <Field label="Merchant ID" name="merchant_id" />
          ) : null}

          {['PUT', 'POST', 'PATCH'].indexOf(this.state.method) > -1 && [
            <SelectField
              label="Content-Type"
              name="content_type"
              key="content_type"
            >
              <option value="application/x-www-form-urlencoded">
                URL Encoded
              </option>
              <option value="application/json">JSON</option>
              <option value="multipart/form-data">Multipart</option>
            </SelectField>,
            <TextAreaField label="Request Body" name="body" key="body" />,
            <FileField
              label="Attach File"
              name="file"
              key="file"
              value={file}
              onChange={e => this.handleChange(e, 'file')}
            />,
            file.length > 0 ? (
              <Field label="File Name" name="file_name" key="file_name" />
            ) : null,
          ]}

          <AsyncButton
            text="OK"
            class="btn"
            pendingClass="small spinner"
            onSubmit={body => {
              let url = body.url;
              delete body.url;

              if (!body.file) {
                body.file = null;
              }

              return adminFormUpload(body, '/api/' + url).then(response => {
                if (response.data.success) {
                  notifySuccess('API Request successful');
                  // closeModal();
                  openModal(
                    <BaseModal header="Api Response:" noPadding>
                      <div class="code" style={{ width: '650px' }}>
                        {JSON.stringify(response.data.data, null, 4)}}
                      </div>
                    </BaseModal>
                  );
                } else {
                  notifyError(response.data.errors.join(', '));
                }
              });
            }}
          />
        </Form>
      </div>
    );
  }
}

MakeAPICall.title = 'Make Raw API Call';
