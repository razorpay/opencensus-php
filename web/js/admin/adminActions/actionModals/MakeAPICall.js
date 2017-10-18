import React, { Component } from 'react';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminFormUpload } from 'util/fetch';

export default class MakeAPICall extends Component {
  constructor() {
    super();
    this.state = {
      auth: '',
    };
    this.handleAuthChange = this.handleAuthChange.bind(this);
  }

  handleAuthChange(e) {
    this.setState({
      auth: e.target.value,
    });
  }

  render() {
    return (
      <div>
        <header>{MakeAPICall.title}</header>
        <Form>
          <Field label="URL" name="url" />
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
              return adminFormUpload(form, '/api/' + data.url)
                .then(response => {
                  if (response.data.success) {
                    notifySuccess('API Request successful');
                    closeModal();
                  } else {
                    response.data.errors.map(error => notifyError(error));
                  }
                })
                .catch(err => {
                  notifyError('The API request failed on the dashboard side.');
                });
            }}
          />
        </Form>
      </div>
    );
  }
}

MakeAPICall.title = 'Make Raw API Call';
