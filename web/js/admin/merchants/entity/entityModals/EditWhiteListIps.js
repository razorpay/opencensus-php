import React, { Component } from 'react';
import { observer } from 'mobx-react';

import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { SelectModeButton, TextAreaField } from 'ui/Field';

import { isIpAddress } from 'rzp/utils/validators';

import { closeModal, notifyError, notifySuccess } from 'common/modal';
import { adminPut } from 'common/fetch';

const verifyAddresses = ipAddresses => ipAddresses.every(isIpAddress);

const splitAddressesString = addressesString =>
  addressesString
    .replace(' ', '')
    .split(',')
    .filter(ipAdd => !!ipAdd);

@observer
export default class EditWhiteListIps extends Component {
  handleSave = ({ whitelisted_ips_test = '', whitelisted_ips_live = '' }) => {
    whitelisted_ips_live = splitAddressesString(whitelisted_ips_live);
    whitelisted_ips_test = splitAddressesString(whitelisted_ips_test);

    if (
      !verifyAddresses(whitelisted_ips_live) ||
      !verifyAddresses(whitelisted_ips_test)
    ) {
      notifyError('Please ensure all entries are valid IP Addresses');
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
    let { whitelisted_ips_live, whitelisted_ips_test } = details;
    return (
      <BaseModal header="Edit Whitelist IPs">
        <Form
          class="full-span full-elements edit-whilelist-ip"
          style={{ width: '500px' }}
        >
          <TextAreaField
            label="Whitelisted IPs in Live Mode"
            name="whitelisted_ips_live"
            defaultValue={
              whitelisted_ips_live ? whitelisted_ips_live.join(',') : ''
            }
          />

          <TextAreaField
            label="Whitelisted IPs in Test Mode"
            name="whitelisted_ips_test"
            defaultValue={
              whitelisted_ips_test ? whitelisted_ips_test.join(',') : ''
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
