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
    const { merchantId } = this.props;
    return (
      <ModalContent header={'Assign Terminal'}>
        <Form class="entity-container" style={{ width: '600px' }}>
          <Field label="Terminal id" defaultValue={merchantId} disabled />
          <Field label="Terminal Category" name="category" />
          <Field label="Terminal Network Category" name="network_category" />
          <Field label="Gateway Merchant Id" name="gateway_merchant_id" />
          <Field label="Gateway Merchant Id 2" name="gateway_merchant_id2" />
          <Field label="Gateway Terminal Id" name="gateway_terminal_id" />
          <Field
            label="Gateway Terminal Password 2"
            name="gateway_terminal_password2"
            type="password"
          />

          <Field label="Gateway Access Code" name="gateway_access_code" />
          <Field label="Gateway Secure Secret" name="gateway_secure_secret" />
          <Field
            label="Gateway Secure Secret 2"
            name="gateway_secure_secret2"
          />

          <Field label="Gateway Recon Password" name="gateway_recon_password" />

          <SelectField
            name="card"
            label="Card Allowed (Always Yes for HDFC)"
            defaultValue=""
          >
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="upi" label="UPI" defaultValue="">
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="netbanking"
            label="Netbanking Allowed"
            defaultValue=""
          >
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="emandate" label="Emandate Allowed" defaultValue="">
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="cardless_emi"
            label="CardlessEMI Allowed"
            defaultValue=""
          >
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="tpv" label="TPV" defaultValue="">
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
            <option value="2">Both</option>
          </SelectField>

          <SelectField name="corporate" label="Corporate" defaultValue="">
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="currency" label="Currency" defaultValue="">
            <option value="" />
            {CurrencyData.data.map(({ code }) => (
              <option value={code}>{code}</option>
            ))}
          </SelectField>

          <SelectField name="emi" label="Emi" defaultValue="">
            <option value="">-NA-</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="emi_duration"
            label="Emi Duration"
            defaultValue={''}
          >
            <option value="">-NA-</option>
            <option value="3">3</option>
            <option value="6">6</option>
            <option value="9">9</option>
            <option value="12">12</option>
            <option value="18">18</option>
            <option value="24">24</option>
          </SelectField>

          <SelectField
            name="emi_subvention"
            label="Emi Subvention"
            defaultValue={''}
          >
            <option value="">-NA-</option>
            <option value="merchant">Merchant</option>
            <option value="customer">Customer</option>
          </SelectField>

          <SelectField
            name="international"
            label="International"
            defaultValue={''}
          >
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="terminal_mode"
            label="Terminal Mode"
            defaultValue={''}
          >
            <option value="" />
            <option value="3">Dual</option>
            <option value="1">Auth-Capture</option>
            <option value="2">Purchase</option>
          </SelectField>

          <Field label="Master card mpan" name="mc_mpan" />
          <Field label="Visa mpan" name="visa_mpan" />
          <Field label="Rupay mpan" name="rupay_mpan" />
          <Field label="VPA" name="vpa" />

          <Field label="Account Number" name="account_number" />

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
            confirm={'Are you sure you want to assign this terminal?'}
          >
            Ok
          </AsyncButton>
        </Form>
      </ModalContent>
    );
  }
}
