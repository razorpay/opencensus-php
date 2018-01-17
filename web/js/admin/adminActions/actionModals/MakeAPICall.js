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

import fetch from 'common/fetch';

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
            <TextAreaField label="URL Encoded Body" name="body" key="body" />,
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
            onSubmit={data => {
              let auth = data.mode;
              if (data.merchant_id) {
                auth += '_' + data.merchant_id;
              }

              let body = new FormData();
              if (data.body) {
                data.body
                  .trim()
                  .split('&')
                  .reduce((body, pair) => {
                    pair = pair.split('=');
                    body.append(
                      [decodeURIComponent(pair[0])],
                      decodeURIComponent(pair[1])
                    );
                    return body;
                  }, body);
              }

              if (data.file && data.file[0]) {
                if (!data.file_name) {
                  return notifyError('Please provide file name');
                }
                body.append(data.file_name, data.file[0]);
              }

              return fetch({
                url: `/admin/api/${auth}/${data.url}`,
                method: data.method,
                data: body,
              }).then(response => {
                if (response) {
                  openModal(
                    <BaseModal header="Api Response" noPadding>
                      <div class="code" style={{ width: '650px' }}>
                        {JSON.stringify(response, null, 4)}}
                      </div>
                    </BaseModal>
                  );
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
