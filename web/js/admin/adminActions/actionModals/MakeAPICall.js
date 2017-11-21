import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminFormUpload } from 'util/fetch';

@withRouter
export default class MakeAPICall extends Component {
  constructor() {
    super();
    this.state = {
      auth: '',
    };
  }

  handleAuthChange = e => {
    this.setState({
      auth: e.target.value,
    });
  };

  render() {
    return (
      <div class="makeapicall">
        <Form class="full-span" style={{ width: '650px' }}>
          <div class="field url">
            <label>URL</label>
            <span class="base-url">https://api.razorpay.com/v1/</span>
            <input name="url" placeholder="relative/url" />
          </div>
          <SelectMode />
          <SelectField label="Request Method" name="method">
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="DELETE">DELETE</option>
            <option value="PATCH">PATCH</option>
          </SelectField>
          <SelectField
            label="AUTH"
            name="auth"
            onChange={this.handleAuthChange}
          >
            <option value="admin">Admin</option>
            <option value="proxy">Merchant</option>
            <option value="internal">Internal</option>
          </SelectField>

          {this.state.auth === 'proxy' ? (
            <Field label="Merchant ID" name="merchant_id" />
          ) : (
            ''
          )}

          <AsyncButton
            text="OK"
            class="btn"
            pendingClass="small spinner"
            onSubmit={data => {
              let form = { ...data };
              form.file = null;
              form.file_name = null;
              form.content_type = 'application/x-www-form-urlencoded';
              return adminFormUpload(form, '/api/' + data.url).then(
                response => {
                  if (response) {
                    notifySuccess('API Request successful');
                    closeModal();
                  }
                }
              );
            }}
          />
        </Form>
      </div>
    );
  }
}

MakeAPICall.title = 'Make Raw API Call';
