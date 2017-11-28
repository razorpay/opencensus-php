import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import Field, {
  SelectMode,
  SelectField,
  DateField,
  SwitchField,
  TimeField,
} from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { getMappingFor } from '../entity-resources';

import { adminPost } from 'util/fetch';

const WALLET_MAP = getMappingFor('wallet');
const CARD_NETWORK_MAP = getMappingFor('network');

export default class CreateOffer extends Component {
  state = {};

  cleanFields(data) {
    let offer = Object.assign({}, data);

    // 1. iins is for only netbanking, wallet, upi
    if (
      ['netbanking', 'wallet', 'upi'].indexOf(offer['payment_method']) !== -1
    ) {
      delete offer['iins'];
    } else if (offer['iins']) {
      offer['iins'] = offer['iins'].split(','); // Convert command separate values to array
    }

    // 2. Max payment count to be sent only when payment method = card
    if (offer['payment_method'] !== 'card') {
      delete offer['max_payment_count'];
    }

    // 3. Payment method is not required for upi
    if (offer['payment_method'] === 'upi') {
      delete offer['payment_network'];
    }

    // 4. Convert to array
    if (offer['linked_offer_ids']) {
      offer['linked_offer_ids'] = offer['linked_offer_ids'].split(',');
    }

    // 5. Percent rate has limit 0-10000 (view takes from 0-100)
    offer['percent_rate'] = offer['percent_rate'] * 100;

    // 6. Form the start and end time in unix timestamp form date and time taken separately for both start and end date
    let offsetStart = offer.starts_at_time.split(':');
    offsetStart = offsetStart[0] * 60 * 60 + offsetStart[1] * 60;

    let offsetEnd = offer.ends_at_time.split(':');
    offsetEnd = offsetEnd[0] * 60 * 60 + offsetEnd[1] * 60;

    if (offer.starts_at) {
      offer.starts_at =
        new Date(offer.starts_at).getTime() / 1000 + offsetStart;
    }
    if (offer.ends_at) {
      offer.ends_at =
        new Date(offer.ends_at).getTime() / 1000 + offsetEnd * 1000;
    }

    delete offer.starts_at_time;
    delete offer.ends_at_time;

    // Remove keys with null/empty value
    Object.keys(offer).forEach(function(key) {
      if (!offer[key]) {
        delete offer[key];
      }
    });

    return offer;
  }

  handleConfirm = body => {
    const mode = body.mode;
    delete body.mode;

    body = this.cleanFields(body);

    return adminPost({
      route_name: 'offer_create',
      merchant_id: this.props.merchantId,
      mode,
      body,
    })
      .then(response => {
        if (response) {
          notifySuccess('Offer created successfully.');
          closeModal();
          this.props.props.fetchMerchantOffers();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    return (
      <BaseModal header="Create Offer">
        <Form class="full-span full-elements" style={{ width: '500px' }}>
          <SelectMode />
          <Field label="Name" name="name" />

          <SelectField
            label="Payment Method"
            name="payment_method"
            onChange={e => {
              this.setState({
                payment_method: e.currentTarget.value,
              });
            }}
          >
            <option value="">All</option>
            <option value="card">Card</option>
            <option value="netbanking">Netbanking</option>
            <option value="emi">EMI</option>
            <option value="upi">UPI</option>
            <option value="wallet">Wallet</option>
          </SelectField>

          {['netbanking', 'wallet', 'upi'].indexOf(
            this.state.payment_method
          ) !== -1 && (
            <SelectField label="Payment Method Type" name="payment_method_type">
              <option value="">All</option>
              <option value="credit">Credit</option>
              <option value="debit">Debit</option>
            </SelectField>
          )}

          {['netbanking', 'wallet', 'upi'].indexOf(
            this.state.payment_method
          ) === -1 && (
            <SelectField label="Payment Network" name="payment_network">
              {Object.keys(CARD_NETWORK_MAP).map(key => (
                <option key={key} value={key}>
                  {CARD_NETWORK_MAP[key]}
                </option>
              ))}
            </SelectField>
          )}

          {['upi'].indexOf(this.state.payment_method) === -1 &&
            do {
              if (['upi', 'wallet'].indexOf(this.state.payment_method) === -1) {
                <Field label="Issuer" name="issuer" />;
              } else if (this.state.payment_method === 'wallet') {
                <SelectField label="Issuer" name="issuer">
                  {Object.keys(WALLET_MAP).map(key => (
                    <option key={key} value={key}>
                      {WALLET_MAP[key]}
                    </option>
                  ))}
                </SelectField>;
              }
            }}

          {['netbanking', 'wallet', 'upi'].indexOf(
            this.state.payment_method
          ) === -1 && (
            <Field
              label="iins"
              name="iins"
              placeholder="Enter comma(,) separated values"
            />
          )}

          <Field
            label="Percent Rate"
            name="percent_rate"
            placeholder="Eg: 45.25"
          />
          <Field label="Max Cashback" name="max_cashback" />
          <Field label="Flat Cashback" name="flat_cashback" />
          <Field label="Min Amount" name="min_amount" />
          <Field
            label="Linked Offer ids"
            name="linked_offer_ids"
            placeholder="Enter comma(,) separated values"
          />

          {/* Starts at */}
          <DateField name="starts_at" label="Starts at" />
          <TimeField name="starts_at_time" defaultValue="00:00" />

          {/* Ends at */}
          <DateField name="ends_at" label="Ends at" required />
          <TimeField name="ends_at_time" defaultValue="00:00" />

          <SwitchField
            name="type"
            label="Type"
            defaultValue={'deferred'}
            disabledLabel="Instant"
            enabledLabel="Deferred"
            enabledValue="deferred"
            disabledValue="instant"
          />

          <SwitchField
            label="Display on Checkout"
            name="checkout_display"
            defaultValue="0"
            disabledLabel="False"
            enabledLabel="True"
          />

          <Field label="Display Text" name="display_text" />
          <Field label="Error Message" name="error_message" />
          <Field label="Terms" name="terms" required />

          <AsyncButton
            text="OK"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleConfirm}
          />
        </Form>
      </BaseModal>
    );
  }
}
