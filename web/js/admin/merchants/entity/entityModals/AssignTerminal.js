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

const gatewayMapping = {
  hdfc: 'HDFC',
  amex: 'Amex',
  atom: 'Atom',
  bajajfinserv: 'Bajaj Finserv',
  cardless_emi: 'Cardless EMI',
  axis_migs: 'Axis MIGS',
  axis_genius: 'Axis Genius',
  ezeclick: 'Ezeclick',
  // paytm: 'Paytm',
  wallet_payzapp: 'Payzapp',
  wallet_payumoney: 'Payumoney',
  wallet_olamoney: 'Olamoney',
  wallet_mpesa: 'Vodafone Mpesa',
  upi_icici: 'UPI/ICICI',
  upi_hulk: 'UPI/HULK',
  upi_mindgate: 'UPI/Mindgate',
  upi_axis: 'UPI/Axis',
  aeps_icici: 'AEPS/ICICI',
  wallet_airtelmoney: 'Airtelmoney',
  wallet_freecharge: 'Freecharge',
  wallet_jiomoney: 'Jiomoney',
  wallet_sbibuddy: 'SBI Buddy',
  paytm: 'Paytm',
  mobikwik: 'Mobikwik',
  billdesk: 'Billdesk',
  ebs: 'EBS',
  first_data: 'FirstData',
  kotak: 'Kotak',
  netbanking_hdfc: 'Netbanking HDFC',
  netbanking_corporation: 'Netbanking Corporation',
  netbanking_kotak: 'Netbanking KOTAK',
  netbanking_axis: 'Netbanking AXIS',
  netbanking_airtel: 'Netbanking AIRTEL',
  netbanking_equitas: 'Netbanking Equitas',
  netbanking_icici: 'Netbanking ICICI',
  netbanking_federal: 'Netbanking Federal',
  netbanking_indusind: 'Netbanking Indusind',
  netbanking_idfc: 'Netbanking Idfc',
  netbanking_rbl: 'Netbanking RBL',
  netbanking_pnb: 'Netbanking PNB',
  netbanking_obc: 'Netbanking OBC',
  netbanking_csb: 'Netbanking CSB',
  netbanking_bob: 'Netbanking BOB',
  netbanking_allahabad: 'Netbanking Allahabad',
  netbanking_vijaya: 'Netbanking Vijaya',
  cybersource: 'Cybersource',
  hitachi: 'Hitachi',
  wallet_openwallet: 'RZP Open Wallet',
  card_fss: 'Card FSS',
  enach_rbl: 'eNach RBL',
  enach_npci_netbanking: 'eNach NPCI Netbanking',
  bt_yesbank: 'Bank Transfer - Yes Bank',
  bt_kotak: 'Bank Transfer - Kotak',
  bt_dashboard: 'Bank Transfer - Dashboard (Test)',
  emi_sbi: 'SBI EMI',
};

const HDFC_gatewayMapping = {
  hdfc: 'HDFC',
  wallet_payzapp: 'Payzapp',
  upi_hulk: 'UPI/HULK',
  upi_mindgate: 'UPI/Mindgate',
  cybersource: 'Cybersource',
};

const gatewayAcquirerMapping = {
  hdfc: 'HDFC',
  axis: 'Axis',
  icic: 'ICICI',
  ratn: 'RBL',
  barb: 'Bank of Baroda',
  fss: 'FSS',
  zestmoney: 'ZestMoney',
  earlysalary: 'EarlySalary',
  amex: 'Amex',
  yesb: 'Yes Bank',
};

const HDFC_gatewayAcquirerMapping = {
  hdfc: 'HDFC',
};

const terminalTypesMapping = [
  { value: 'non_recurring', name: 'Non Recurring' },
  { value: 'recurring_3ds', name: 'Recurring 3DS' },
  { value: 'recurring_non_3ds', name: 'Recurring Non 3DS' },
  { value: 'ivr', name: 'IVR' },
  { value: 'numeric_account', name: 'Numeric Account' },
  { value: 'alpha_numeric_account', name: 'Alpha Numeric Account' },
  { value: 'business_banking', name: 'Business Banking' },
  { value: 'no_2fa', name: 'No 2FA' },
  { value: 'pay', name: 'UPI Pay' },
  { value: 'collect', name: 'UPI Collect' },
  { value: 'pin', name: 'PIN' },
  { value: 'bharat_qr', name: 'Bharat QR' },
  { value: 'debit_recurring', name: 'Debit Recurring' },
  { value: 'direct_settlement', name: 'Direct Settlement' },
  { value: 'moto', name: 'Moto' },
];

const HDFC_terminalTypesMapping = [
  { value: 'non_recurring', name: 'Non Recurring' },
  { value: 'ivr', name: 'IVR' },
  { value: 'numeric_account', name: 'Numeric Account' },
  { value: 'alpha_numeric_account', name: 'Alpha Numeric Account' },
  { value: 'no_2fa', name: 'No 2FA' },
  { value: 'pay', name: 'UPI Pay' },
  { value: 'collect', name: 'UPI Collect' },
  { value: 'bharat_qr', name: 'Bharat QR' },
  { value: 'direct_settlement', name: 'Direct Settlement' },
];

const gatewayMappingOnAddMessages = {
  paytm: 'Direct settlement will be enforced on for gateway Paytm',
};

const gatewayMappingTerminalTypesDefaults = {
  paytm: {
    value: 'direct_settlement',
    name: 'Direct Settlement',
  },
};

export default class TerminalForm extends Component {
  state = {
    pricingPlans: {},
    alertMessageForTerminalType: null,
  };

  // Creates terminal
  handleCreate = body => {
    let file;
    const gatewayMappingTerminalTypeValue =
      gatewayMappingTerminalTypesDefaults[body.gateway];

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

      if (
        gatewayMappingTerminalTypeValue &&
        !temp[gatewayMappingTerminalTypeValue.value]
      ) {
        temp[gatewayMappingTerminalTypeValue.value] = '1';
      }

      body.type = temp;
    } else {
      if (gatewayMappingTerminalTypeValue) {
        body.type = {
          [gatewayMappingTerminalTypeValue.value]: '1',
        };
      }
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

  handleAlertMessageForTerminalType = message =>
    this.setState({ alertMessageForTerminalType: message });

  handleGateway = e => {
    const value = e.target.value;

    if (gatewayMappingOnAddMessages.hasOwnProperty(value)) {
      this.handleAlertMessageForTerminalType(
        gatewayMappingOnAddMessages[value]
      );
    } else {
      if (this.state.alertMessageForTerminalType) {
        this.handleAlertMessageForTerminalType();
      }
    }
  };

  render() {
    const { isEditMode, handleEdit, entity } = this.props;
    const gateways = isOrgHDFC() ? HDFC_gatewayMapping : gatewayMapping;
    const gatewayAcquirers = isOrgHDFC()
      ? HDFC_gatewayAcquirerMapping
      : gatewayAcquirerMapping;

    const terminalTypes = isOrgHDFC()
      ? HDFC_terminalTypesMapping
      : terminalTypesMapping;

    let selectedTypes = [];
    if (entity && entity.type) {
      selectedTypes = entity.type.map(elem => ({
        value: elem,
      }));
    }

    return (
      <ModalContent header={`${isEditMode ? 'Edit' : 'Assign'} Terminal`}>
        <div class="m-b">
          <strong class="text-danger">
            {isEditMode
              ? 'Warning: Terminals can only be edit before any transactions happen through them'
              : 'Warning: The terminal once assigned can not be changed'}
          </strong>
        </div>

        <Form class="entity-container" style={{ width: '600px' }}>
          {isEditMode && (
            <Field label="Terminal id" defaultValue={entity.id} disabled />
          )}
          {isEditMode && (
            <Field
              label="Merchant id"
              defaultValue={entity.merchant_id}
              disabled
            />
          )}
          {!isEditMode && (
            <SelectField name="mode" label="Mode" defaultValue="live">
              <option value="test">Test</option>
              <option value="live">Live</option>
            </SelectField>
          )}

          <SelectField
            name="gateway"
            label="Gateway"
            defaultValue={isEditMode ? entity.gateway : ''}
            disabled={isEditMode}
            onChange={this.handleGateway}
          >
            {Object.keys(gateways).map(key => (
              <option key={key} value={key}>
                {gateways[key]}
              </option>
            ))}
          </SelectField>

          <SelectField
            name="gateway_acquirer"
            label="Gateway Acquirer"
            defaultValue={''}
          >
            <option value="">NA</option>
            {Object.keys(gatewayAcquirers).map(key => (
              <option key={key} value={key}>
                {gatewayAcquirers[key]}
              </option>
            ))}
          </SelectField>

          <Field label="Terminal Category" name="category" />
          <Field label="Terminal Network Category" name="network_category" />
          <Field label="Gateway Merchant Id" name="gateway_merchant_id" />
          <Field label="Gateway Merchant Id 2" name="gateway_merchant_id2" />
          <Field label="Gateway Terminal Id" name="gateway_terminal_id" />
          <Field
            label="Gateway Terminal Password"
            name="gateway_terminal_password"
            defaultValue={isEditMode ? entity.gateway_terminal_password : ''}
            type="password"
          />
          {!isEditMode && (
            <Field
              label="Confirm Gateway Terminal Password"
              name="gateway_terminal_password_confirmation"
              type="password"
            />
          )}

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

          {!isEditMode && (
            <FileField
              label="Gateway Client Certificate"
              name="file"
              infoMsg="Certificate file for FirstData"
            />
          )}

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

          <SelectField
            name="bank_transfer"
            label="Bank Transfer"
            defaultValue={
              isEditMode && entity.bank_transfer !== null
                ? entity.bank_transfer | 0
                : ''
            }
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

          <SelectField name="corporate" label="Banking Type" defaultValue="">
            <option value="" />
            <option value="0">Retail</option>
            <option value="1">Corporate</option>
            <option value="2">Both</option>
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

          {!isEditMode && (
            <SelectField name="shared" label="Shared" defaultValue={''}>
              <option value="" />
              <option value="1">Yes</option>
              <option value="0">No</option>
            </SelectField>
          )}

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

          <div class="types-select">
            <MultiSelectField
              class="terminal-types"
              label="Types"
              name="type"
              options={terminalTypes}
              trackBy="value"
              keys={['name']}
              defaultValue={selectedTypes}
              placeholder="Select Types"
            />
          </div>

          <div class="m-t m-b" />
          {this.state.alertMessageForTerminalType && (
            <div class="text-success">
              {this.state.alertMessageForTerminalType}
            </div>
          )}
          <AsyncButton
            text="Cancel"
            class="btn btn-default"
            pendingClass="small spinner"
            onSubmit={closeModal}
          />
          <AsyncButton
            onSubmit={handleEdit ? handleEdit : this.handleCreate}
            class="btn"
            pendingClass="small spinner"
            confirm={`Are you sure you want to ${
              handleEdit ? 'edit' : 'assign'
            } this terminal?`}
          >
            Ok
          </AsyncButton>
        </Form>
      </ModalContent>
    );
  }
}
