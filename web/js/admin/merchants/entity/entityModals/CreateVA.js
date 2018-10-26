import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';

import { getFormattedAmount } from 'common/util';
import Form from 'ui/Form';
import Duplex from 'ui/Duplex';
import Field, { CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import {
  notifyError,
  notifySuccess,
  closeModal,
  openModal,
  Modal,
} from 'common/modal';

import { adminPost } from 'common/fetch';

export default class CreateVA extends Component {
  onSubmit = body => {
    const receiverTypes = [];

    if (body.bank_account === '1') {
      receiverTypes.push('bank_account');
    }
    if (body.qr_code === '1') {
      receiverTypes.push('qr_code');
    }

    if (!receiverTypes.length) {
      return notifyError(
        'Please select at least one of `bank_account` and `qr_code`'
      );
    }

    const payload = {
      receivers: {
        types: receiverTypes,
      },
    };
    if (body.description) {
      payload.description = body.description;
    }
    if (body.notes) {
      payload.notes = body.notes;
    }
    if (body.amount_expected) {
      payload.amount_expected = body.amount_expected;
    }

    return adminPost({
      url: `live_${this.props.merchantId}/virtual_accounts`,
      data: payload,
    })
      .then(response => {
        if (response) {
          notifySuccess('Virtual Account created.');
          closeModal();
          openModal(<VADetails data={response} />);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    return (
      <ModalContent header="Create Virtual Account">
        <Form class="full-span full-elements" style={{ width: '500px' }}>
          <CheckField label="Bank Account" name="bank_account" />
          <CheckField label="QR Code" name="qr_code" />
          <Field label="Description" name="description" />
          <Field label="Notes" name="notes[comment]" />
          <Field label="Amount (in paise)" name="amount_expected" />

          <AsyncButton
            text="Create"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.onSubmit}
          />
        </Form>
      </ModalContent>
    );
  }
}

class VADetails extends Component {
  render() {
    const data = this.props.data;
    const bankDetails = data.receivers.find(r => r.entity === 'bank_account');
    const qrDetails = data.receivers.find(r => r.entity === 'qr_code');
    const model = {
      id: data.id,
      qr: qrDetails,
      ba: bankDetails,
      amount: data.amount_expected,
    };
    return (
      <ModalContent header="Virtual Account">
        <div style={{ border: '1px solid #ddd' }}>
          <Duplex model={model} fields={fields} />
        </div>
      </ModalContent>
    );
  }
}

const fields = [
  item => ['Virtual Account ID', <b>{item.id}</b>],
  item => item.ba && ['Bank Account ID', item.ba.id],
  item => item.ba && ['IFSC', item.ba.ifsc],
  item => item.ba && ['Account Number', item.ba.account_number],
  item => item.qr && ['QR Code ID', item.qr.id],
  item =>
    item.qr && [
      'Short URL',
      <a class="link" target="_blank" href={item.qr.short_url}>
        {item.qr.short_url}
      </a>,
    ],
  item =>
    item.amount && ['Amount Expected', '₹' + getFormattedAmount(item.amount)],
];
