import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';

import { isOrgHDFC } from 'admin/user';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField, FileField, CheckField } from 'ui/Field';
import { adminFetch, adminPost, adminFormUpload2 } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import MultiSelectField from 'ui/MultiSelectField';

// TODO: import currency data in a better way
import CurrencyData from './currency.json';

export default class TerminalForm extends Component {
  state = { pricingPlans: {} };

  // Creates terminal
  handleCreate = body => {
    let file;

    if (
      body['gateway_terminal_password'] &&
      body['gateway_terminal_password_confirmation'] !==
        body['gateway_terminal_password']
    ) {
      notifyError('Password and confirmation password entered do not match');
    } else {
      delete body['gateway_terminal_password_confirmation'];
    }

    if (body.type) {
      let temp = {};
      body.type.split(',').forEach(elem => {
        temp[elem] = '1';
      });
      body.type = temp;
    }

    if (body.file) {
      file = body.file[0];
    }

    if (
      body.gateway === 'first_data' &&
      (file && file.type !== 'application/x-pkcs12')
    ) {
      notifyError(JSON.stringify('Invalid certificate file'));

      return;
    }

    if (file) {
      body.gateway_client_certificate = file;
      delete body.file;
    }

    let mode = body.mode;
    delete body.mode;

    if (body.terminal_mode) {
      body.mode = body.terminal_mode; // Terminal mode is sent as mode. And mode(test/live) is just for api url.
    }

    delete body.terminal_mode;

    return adminFormUpload2(
      body,
      `/admin/api/${mode}/merchants/${this.props.merchantId}/terminals`
    )
      .then(response => {
        if (response.data.success) {
          notifySuccess('Terminal assigned successfully.');
          closeModal();

          // We're displaying only live terminal on right side of merchant details, so update only for live mode
          if (mode === 'live') {
            this.props.props.updateTerminal(response.data.data);
          }
        } else {
          response.data.errors.map(error => notifyError(error));
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    console.log('here');
    console.log(this.props);
    console.log(this.props.props);
    console.log(this.props.props.Model);
    console.log(this.props.props.model);
    const { merchantId } = this.props;
    return (
      <ModalContent header={'Create Terminal'}>
        <Form class="entity-container" style={{ width: '600px' }}>
          <Field label="Terminal id" defaultValue={merchantId} disabled />
          <SelectField name="mode" label="Mode" defaultValue="live">
            <option value="test">Test</option>
            <option value="live">Live</option>
          </SelectField>
          <SelectField name="gateway" label="Gateway" defaultValue="hitachi">
            <option value="hitachi">Hitachi</option>
          </SelectField>
          <Field label="Terminal Category" name="category" />{' '}
          {/* Prefil this */}
          <Field label="Gateway Merchant Id" name="gateway_merchant_id" />
          <Field label="Gateway Terminal Id" name="gateway_terminal_id" />
          <SelectField name="currency" label="Currency" defaultValue="INR">
            <option value="" />
            {CurrencyData.data.map(({ code }) => (
              <option value={code}>{code}</option>
            ))}
          </SelectField>
          <div class="m-t m-b" />
          <AsyncButton
            text="Cancel"
            class="btn btn-default"
            pendingClass="small spinner"
            onSubmit={closeModal}
          />
          <AsyncButton
            onSubmit={this.handleCreate}
            class="btn"
            pendingClass="small spinner"
            confirm={'Are you sure you want to create this terminal?'}
          >
            Ok
          </AsyncButton>
        </Form>
      </ModalContent>
    );
  }
}
