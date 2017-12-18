import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField, FileField, CheckField } from 'ui/Field';
import { adminFetch, adminPost, adminFormUpload2 } from 'util/fetch';
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
  upi_hulk: 'UPI/HULK',
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
  icic: 'ICICI',
};

export default class TerminalForm extends Component {
  state = { pricingPlans: {} };

  // Creates terminal
  handleCreate = body => {
    let file;

    for (let key in body.type) {
      if (body.type[key] == '0') {
        delete body.type[key];
      }
    }
    if (!Object.keys(body.type).length) {
      delete body.type;
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

    return adminFormUpload2(
      body,
      '/admin/merchant/' + this.props.merchantId + '/terminal'
    )
      .then(response => {
        if (response.data.success) {
          notifySuccess('Terminal assigned successfully.');
          closeModal();

          // We're displaying only live terminal on right side of merchant details, so update only for live mode
          if (body.mode === 'live') {
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
    const { isEditMode, handleEdit, entity } = this.props;
    return (
      <BaseModal header={`${isEditMode ? 'Edit' : 'Assign'} Terminal`}>
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
          >
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
            <option value="">NA</option>
            {Object.keys(gatewayAcquirerMapping).map(key => (
              <option key={key} value={key}>
                {gatewayAcquirerMapping[key]}
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

          <Field label="Gateway Access Code" name="gateway_access_code" />
          <Field label="Gateway Secure Secret" name="gateway_secure_secret" />

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

          <SelectField name="tpv" label="TPV" defaultValue="">
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="corporate" label="Corporate" defaultValue="">
            <option value="" />
            <option value="1">Yes</option>
            <option value="0">No</option>
          </SelectField>

          <SelectField name="currency" label="Currency" defaultValue="">
            <option value="" />
            <option value="INR">INR</option>
            <option value="USD">USD</option>
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

          <CheckField
            label="Non recurring"
            name="type[non_recurring]"
            defaultChecked={
              entity && entity.type ? entity.type['non_recurring'] : ''
            }
          />
          <CheckField
            label="Recurring 3DS"
            name="type[recurring_3ds]"
            defaultChecked={
              entity && entity.type ? entity.type['recurring_3ds'] : ''
            }
          />
          <CheckField
            label="Recurring Non 3DS"
            name="type[recurring_non_3ds]"
            defaultChecked={
              entity && entity.type ? entity.type['recurring_non_3ds'] : ''
            }
          />
          <CheckField
            label="IVR"
            name="type[ivr]"
            defaultChecked={entity && entity.type ? entity.type['ivr'] : ''}
          />

          <div class="m-t m-b" />
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
            confirm={
              handleEdit
                ? 'Are you sure you want to edit this terminal?'
                : 'Any previously assigned plan for the merchant will be replace with selected.'
            }
          >
            Ok
          </AsyncButton>
        </Form>
      </BaseModal>
    );
  }
}
