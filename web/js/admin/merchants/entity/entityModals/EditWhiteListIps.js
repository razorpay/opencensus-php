import React, { Component } from 'react';
import { observer } from 'mobx-react';

import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { SelectModeButton, TextAreaField } from 'ui/Field';

import { isIpAddress } from 'rzp/utils/validators';

import { closeModal, notifyError, notifySuccess } from 'common/modal';
import { adminPut } from 'common/fetch';

const getInvalidAddresses = ipAddresses =>
  ipAddresses.filter(ipAdd => !isIpAddress(ipAdd));

const splitAddressesString = addressesString =>
  addressesString
    .replace(/ /g, '')
    .split(',')
    .filter(ipAdd => !!ipAdd);

@observer
export default class EditWhiteListIps extends Component {
  notifyError = (entries, mode) => {
    notifyError(
      `${entries.join(', ')} in "${mode}" mode ${
        entries.length > 1 ? 'are' : 'is'
      } invalid`,
      7000
    );
  };

  handleSave = ({ whitelisted_ips_test = '', whitelisted_ips_live = '' }) => {
    whitelisted_ips_live = splitAddressesString(whitelisted_ips_live);
    whitelisted_ips_test = splitAddressesString(whitelisted_ips_test);

    const invalidLiveAdd = getInvalidAddresses(whitelisted_ips_live);
    const invalidTestAdd = getInvalidAddresses(whitelisted_ips_test);
    if (invalidLiveAdd.length || invalidTestAdd.length) {
      invalidLiveAdd.length && this.notifyError(invalidLiveAdd, 'live');
      invalidTestAdd.length && this.notifyError(invalidTestAdd, 'test');
      return;
    }

    const data = { whitelisted_ips_live, whitelisted_ips_test };
    return adminPut({
      url: `live/merchants/${this.props.merchantId}`,
      data,
    })
      .then(response => {
        if (response) {
          notifySuccess('IP Addresses added successfully');
          this.props.props.updateDetails(data);
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    const details = this.props.props.merchant.details;
    const { whitelisted_ips_live, whitelisted_ips_test } = details;

    return (
      <BaseModal header="Edit Whitelist IPs">
        <Form
          class="full-span full-elements edit-whilelist-ip"
          style={{ width: '500px' }}
        >
          <TextAreaField
            label="Live Mode Whitelisted IPs"
            name="whitelisted_ips_live"
            defaultValue={
              whitelisted_ips_live ? whitelisted_ips_live.join(', ') : ''
            }
          />

          <TextAreaField
            label="Test Mode Whitelisted IPs"
            name="whitelisted_ips_test"
            defaultValue={
              whitelisted_ips_test ? whitelisted_ips_test.join(', ') : ''
            }
          />

          <AsyncButton
            text="Submit"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleSave}
          />
        </Form>
      </BaseModal>
    );
  }
}
