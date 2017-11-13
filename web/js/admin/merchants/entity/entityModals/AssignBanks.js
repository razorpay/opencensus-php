import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import { SwitchField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';

export default class PricingPlanModal extends Component {
  state = { merchantBanksMapping: {} };

  componentWillMount() {
    adminFetch({
      route_name: 'merchant_get_banks',
      url_params: { id: this.props.props.merchant.details.id },
    }).then(data => {
      let merchantBanksMapping = {};
      let banksList = {};

      for (let key in data.disabled) {
        merchantBanksMapping[key] = data.disabled[key];
        banksList[key] = '0';
      }

      for (let key in data.enabled) {
        merchantBanksMapping[key] = data.enabled[key];
        banksList[key] = '1';
      }

      this.setState({ merchantBanksMapping, banksList });
    });
  }

  /* UI fields for methods */
  getFormFields() {
    const fields = [];

    for (let bank in this.state.banksList) {
      fields.push(
        <label key={bank}>
          <SwitchField name={bank} value={this.state.banksList[bank]} />
          {this.state.merchantBanksMapping[bank]}
        </label>
      );
    }

    return fields;
  }

  handleConfirm = body => {
    const { props } = this.props;

    return confirm(
      'Any previously assigned banks for the merchant will be replace with selected.',
      'Submit'
    ).then(_ => {
      const banksData = {
        banks: Object.keys(body),
      };

      return adminPost({
        route_name: 'merchant_set_banks',
        url_params: {
          id: props.merchant.details.id,
        },
        body: banksData,
      })
        .then(response => {
          notifySuccess('Pricing Plan assigned successfully.');
          closeModal();
        })
        .catch(err => {
          notifyError(JSON.stringify(err.response));
        });
    });
  };

  render() {
    return (
      <BaseModal header="Assign Banks">
        <Form>
          {this.getFormFields()}
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
