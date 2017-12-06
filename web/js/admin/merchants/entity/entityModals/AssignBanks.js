import React, { Component, Fragment } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import { SwitchField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';
import { isWorkflow } from 'util/index';

export default class PricingPlanModal extends Component {
  state = { merchantBanksMapping: {} };

  componentWillMount() {
    adminFetch({
      route_name: 'merchant_get_banks',
      url_params: { id: this.props.merchantId },
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
        <Fragment key={bank}>
          <SwitchField
            name={bank}
            disabledLabel={this.state.merchantBanksMapping[bank]}
            defaultValue={this.state.banksList[bank]}
            nocaption
          />
          <br />
        </Fragment>
      );
    }

    return fields;
  }

  handleConfirm = body => {
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
          id: this.props.merchantId,
        },
        body: banksData,
      })
        .then(response => {
          if (isWorkflow(response)) {
            return;
          }

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
          {!this.state.banksList ? (
            <div class="spinner center" />
          ) : (
            <div>
              {this.getFormFields()}

              <br />
              <br />
              <div class="separate" />
              <AsyncButton
                text="Ok"
                class="btn pull-right"
                pendingClass="small spinner pull-right"
                onSubmit={this.handleConfirm}
              />
              <AsyncButton
                text="Cancel"
                class="btn btn-default pull-right"
                pendingClass="small spinner pull-right"
                onSubmit={closeModal}
              />
            </div>
          )}
        </Form>
      </BaseModal>
    );
  }
}
