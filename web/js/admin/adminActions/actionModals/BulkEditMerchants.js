import React from 'react';
import Form from 'ui/Form';
import Field, { TextAreaField, SelectField } from 'ui/Field';

import { adminPut } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import {
  closeModal,
  notifyError,
  notifySuccess,
  openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

const type = {
  method: 'Method',
  card_networks: 'Card Networks',
  banks: 'Banks',
  hold_funds: 'Hold Funds',
  international: 'International',
};

const sub_type = {
  method: {
    netbanking: 'Net Banking',
    upi: 'UPI',
    emandate: 'Emandate',
    bank_transfer: 'Bank Transfer',
    aeps: 'AEPS',
    emi: 'EMI',
    cardless_emi: 'Cardless Emi',
    debit_card: 'Debit Card',
    credit_card: 'Credit Card',
    paytm: 'Paytm',
    mobikwik: 'Mobikwik',
    payzapp: 'Payzapp',
    payumoney: 'PayuMoney',
    openwallet: 'Open Wallet',
    olamoney: 'Ola Money',
    phonepe: 'PhonePe',
    freecharge: 'FreeCharge',
    jiomoney: 'Jio Money',
    sbibuddy: 'SBI Buddy',
    mpesa: 'Mpesa',
    airtelmoney: 'Airtel Money',
    amazonpay: 'Amazon Pay',
  },
  card_networks: {
    AMEX: 'AMEX',
    DICL: 'DICL',
    MC: 'MC',
    MAES: 'MAES',
    VISA: 'VISA',
    JCB: 'JCB',
    RUPAY: 'RUPAY',
    BAJAJ: 'BAJAJ',
  },
};

const actions = {
  1: 'Enable',
  0: 'Disable',
};

export default class BulkEditMerchants extends React.Component {
  state = {
    type: 'method',
  };

  onSubmit(body) {
    if (!body.merchant_ids) {
      notifyError('Merchant Ids are mandatory.');
      return;
    }

    if (!body.attributes) {
      notifyError('Please select at least one field to update.');
      return;
    }

    let payload = null,
      merchant_ids = body.merchant_ids,
      type = body.attributes.type;

    /* If type is hold_funds or international, request goes to old API: /merchants/bulk, 
       otherwise request will go to new API: /methods/bulkupdate
    */
    if (type === 'hold_funds' || type === 'international') {
      Object.keys(body.attributes).forEach(key => {
        if (!body.attributes[key]) {
          delete body.attributes[key];
        }
      });

      payload = {
        url: `live/merchants/bulk`,
        data: {
          merchant_ids: splitAndFilter(merchant_ids, ','),
          attributes: body.attributes,
        },
      };
    } else {
      const request = { merchants: null, methods: {} };
      const { attributes } = body;
      let sub_type = attributes.sub_type;
      request.merchants = splitAndFilter(merchant_ids, ',');

      if (type === 'method') {
        request.methods[sub_type] = attributes.action;
      } else if (type === 'card_networks') {
        request.methods['card_networks'] = {};
        request.methods.card_networks[sub_type] = parseInt(attributes.action);
      } else if (type === 'banks') {
        if (attributes.action == 1) {
          request.methods.enabled_banks = [];
          request.methods.enabled_banks.push(sub_type);
        } else {
          request.methods.disabled_banks = [];
          request.methods.disabled_banks.push(sub_type);
        }
      }

      payload = {
        url: `live/methods/bulkupdate`,
        data: request,
      };
    }

    adminPut(payload).then(response => {
      if (response) {
        if (response.success == 0) {
          notifyError(`Failed to update the merchants.`);
        } else {
          notifySuccess(
            `${response.success} merchant(s) have been updated successfully.`
          );
          closeModal();
        }
        openModal(
          <ModalContent header="API Response">
            <div class="code" style={{ width: '650px' }}>
              {JSON.stringify(response, null, 4)}}
            </div>
          </ModalContent>
        );
      }
    });
  }

  onTypeChange = e => this.setState({ type: e.target.value });

  renderSubType() {
    if (this.state.type === 'method') {
      return (
        <SelectField name="attributes[sub_type]" label="Sub Type">
          {Object.keys(sub_type.method).map((item, idx) => {
            return (
              <option value={item} key={idx}>
                {sub_type.method[item]}
              </option>
            );
          })}
        </SelectField>
      );
    } else if (this.state.type === 'banks')
      return <Field name="attributes[sub_type]" label="Sub Type" />;
    else if (this.state.type === 'card_networks')
      return (
        <SelectField name="attributes[sub_type]" label="Sub Type">
          {Object.keys(sub_type.card_networks).map((item, idx) => {
            return (
              <option value={item} key={idx}>
                {sub_type.card_networks[item]}
              </option>
            );
          })}
        </SelectField>
      );
    else return null;
  }

  render() {
    return (
      <Form class="full-span edit-merchants-action" onSubmit={this.onSubmit}>
        <TextAreaField
          label="Merchant Ids"
          type="text"
          name="merchant_ids"
          required
          placeholder="Enter comma separated merchant ids"
        />
        <SelectField
          name="attributes[type]"
          label="Type"
          onChange={this.onTypeChange}
        >
          {Object.keys(type).map((item, idx) => {
            return (
              <option value={item} key={idx}>
                {type[item]}
              </option>
            );
          })}
        </SelectField>
        {this.renderSubType()}
        <SelectField name="attributes[action]" label="Action">
          {Object.keys(actions).map((item, idx) => {
            return (
              <option value={item} key={idx}>
                {actions[item]}
              </option>
            );
          })}
        </SelectField>
        <div class="form-actions text-right">
          <button class="btn" type="submit">
            Update
          </button>
        </div>
      </Form>
    );
  }
}

BulkEditMerchants.title = 'Edit Merchants in Bulk';
BulkEditMerchants.permission = 'edit_bulk_merchant';
