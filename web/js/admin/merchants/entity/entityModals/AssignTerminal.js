import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField, FileField } from 'ui/Field';
import { adminFetch, adminPost, adminFormUpload } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';

const gatewayMapping = {
  hdfc: 'HDFC',
  amex: 'Amex',
  atom: 'Atom',
  axis_migs: 'Axis MIGS',
  axis_genius: 'Axis Genius',
  ezeclick: 'Ezeclick',
  // paytm: 'Paytm',
  wallet_payzapp: 'Payzapp',
  wallet_payumoney: 'Payumoney',
  wallet_olamoney: 'Olamoney',
  wallet_mpesa: 'Vodafone Mpesa',
  upi_icici: 'UPI/ICICI',
  upi_mindgate: 'UPI/Mindgate',
  aeps_icici: 'AEPS/ICICI',
  wallet_airtelmoney: 'Airtelmoney',
  wallet_freecharge: 'Freecharge',
  wallet_jiomoney: 'Jiomoney',
  wallet_sbibuddy: 'SBI Buddy',
  mobikwik: 'Mobikwik<',
  billdesk: 'Billdesk',
  ebs: 'EBS',
  first_data: 'FirstData',
  kotak: 'Kotak',
  netbanking_hdfc: 'Netbanking HDFC',
  netbanking_corporation: 'Netbanking Corporation',
  netbanking_kotak: 'Netbanking KOTAK',
  netbanking_axis: 'Netbanking AXIS',
  netbanking_airtel: 'Netbanking AIRTEL',
  netbanking_icici: 'Netbanking ICICI',
  netbanking_federal: 'Netbanking Federal',
  netbanking_indusind: 'Netbanking Indusind',
  netbanking_rbl: 'Netbanking RBL',
  netbanking_pnb: 'Netbanking PNB',
  cybersource: 'Cybersource',
  hitachi: 'Hitachi',
  wallet_openwallet: 'RZP Open Wallet',
};

const gatewayAcquirerMapping = {
  hdfc: 'HDFC',
  axis: 'Axis',
  icici: 'ICICI',
};

export default class AssignTerminal extends Component {
  state = { pricingPlans: {} };

  componentWillMount() {
    adminFetch({
      route_name: 'pricing_get_merchant_plans',
    }).then(data => {
      const pricingPlans = {};

      for (let key in data.items) {
        let value = data.items[key];
        pricingPlans[value.id] = value.name;
      }

      this.setState({ pricingPlans });
    });
  }

  handleConfirm = body => {
    const { props } = this.props;

    return confirm(
      'Any previously assigned plan for the merchant will be replace with selected.',
      'Submit'
    ).then(_ => {
      let file = body.file[0];
      if (
        body.gateway === 'first_data' &&
        file.type !== 'application/x-pkcs12'
      ) {
        notifyError(JSON.stringify('Invalid certificate file'));

        return;
      }
      body.gateway_client_certificate = file;

      return adminFormUpload(
        body,
        '/admin/merchant/' + props.merchant.details.id + '/terminal'
      )
        .then(response => {
          if (response.data.success) {
            notifySuccess('Terminal assigned successfully.');
            closeModal();

            // Post success calculations in 'merchant.terminals' in model
          } else {
            response.data.errors.map(error => notifyError(error));
          }
        })
        .catch(err => {
          notifyError(JSON.stringify(err.response));
        });
    });
  };

  render() {
    return (
      <BaseModal header="Assign Terminal">
        <span>
          <strong>
            Warning: The terminal once assigned can not be changed
          </strong>
        </span>

        <Form>
          <SelectField name="mode" label="Mode" defaultValue="live">
            <option value="test">Test</option>
            <option value="live">Live</option>
          </SelectField>

          <SelectField name="gateway" label="Gateway" defaultValue={''}>
            {Object.keys(gatewayMapping).map(key => (
              <option key={key} value={key}>
                {gatewayMapping[key]}
              </option>
            ))}
          </SelectField>

          <SelectField
            name="gateway_acquirer"
            label="Gateway Acquirer"
            defaultValue={''}
          >
            {Object.keys(gatewayAcquirerMapping).map(key => (
              <option key={key} value={key}>
                {gatewayAcquirerMapping[key]}
              </option>
            ))}
          </SelectField>

          <Field label="Terminal Category" name="category" type="number" />
          <Field label="Terminal Network Category" name="network_category" />
          <Field
            label="Gateway Merchant Id"
            name="gateway_merchant_id"
            type="number"
          />
          <Field label="Gateway Merchant Id 2" name="gateway_merchant_id2" />
          <Field label="Gateway Terminal Id" name="gateway_terminal_id" />
          <Field
            label="Gateway Terminal Password"
            name="gateway_terminal_password"
            type="password"
          />
          <Field
            label="Confirm Gateway Terminal Password"
            name="gateway_terminal_password_confirmation"
            type="password"
          />

          <Field label="Gateway Access Code" name="gateway_access_code" />
          <Field label="Gateway Secure Secret" name="gateway_secure_secret" />

          <FileField
            label="Gateway Client Certificate"
            name="file"
            infoMsg="Certificate file for FirstData"
          />

          <Field label="Gateway Recon Password" name="gateway_recon_password" />

          <SelectField
            name="card"
            label="Card Allowed (Always Yes for HDFC)"
            defaultValue="1"
          >
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="upi" label="UPI" defaultValue="1">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="netbanking"
            label="Netbanking Allowed"
            defaultValue="1"
          >
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="tpv" label="TPV" defaultValue="0">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="corporate" label="Corporate" defaultValue="0">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="currency" label="Currency" defaultValue="INR">
            <option value="INR" selected>
              INR
            </option>
            <option value="USD">USD</option>
          </SelectField>

          <SelectField name="emi" label="Emi" defaultValue="0">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="emi_duration"
            label="Emi Duration"
            defaultValue={''}
          >
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
            <option value="merchant">Merchant</option>
            <option value="customer">Customer</option>
          </SelectField>

          <SelectField name="shared" label="Shared" defaultValue={''}>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="international"
            label="International"
            defaultValue={''}
          >
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField
            name="terminal_mode"
            label="Terminal Mode"
            defaultValue={''}
          >
            <option value="3">Dual</option>
            <option value="1">Auth-Capture</option>
            <option value="2">Purchase</option>
          </SelectField>

          <AsyncButton
            text="Cancel"
            class="btn btn-default"
            pendingClass="small spinner"
            onSubmit={closeModal}
          />
          <AsyncButton
            text="Ok"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleConfirm}
          />
        </Form>
      </BaseModal>
    );
  }
}
