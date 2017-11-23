import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';
import { isWorkflow } from 'util/index';

const methodMapping = {
  null: 'All',
  card: 'Card',
  netbanking: 'Netbanking',
  upi: 'UPI',
  emi: 'EMI',
  wallet: 'Wallet',
  bank_transfer: 'Bank Transfer',
};

const type_list = { Settlement: 'settlement' };

export default class PricingPlanModal extends Component {
  state = { settlementPlans: {} };

  componentWillMount() {
    adminFetch({
      route_name: 'setl_fetch_schedule',
    }).then(data => {
      const settlementPlans = {};

      for (let key in data.items) {
        let value = data.items[key];
        settlementPlans[value.id] = value.name;
      }

      this.setState({ settlementPlans });
    });
  }

  handleSubmit = body => {
    const schedulePlanData = {
      method: body.method,
      schedule_id: body.schedule_id,
      type: body.type,
    };

    return adminPost({
      route_name: 'schedule_assign',
      url_params: {
        id: this.props.merchantId,
      },
      body: schedulePlanData,
    })
      .then(response => {
        if (isWorkflow(response)) {
          return;
        }

        notifySuccess('Schedule Plan assigned successfully.');
        closeModal();
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    return (
      <BaseModal header="Assign Schedule Plan">
        <Form class="full-span full-elements" style={{ width: '350px' }}>
          <SelectField name="type" label="Type" defaultValue={''}>
            {Object.keys(type_list).map(key => (
              <option key={key} value={key}>
                {type_list[key]}
              </option>
            ))}
          </SelectField>

          <SelectField name="schedule_id" label="Schedules" defaultValue={''}>
            {!Object.keys(this.state.settlementPlans).length && (
              <option value="">Loading...</option>
            )}
            {Object.keys(this.state.settlementPlans).map(key => (
              <option key={key} value={key}>
                {this.state.settlementPlans[key]}
              </option>
            ))}
          </SelectField>

          <SelectField name="method" label="Method" defaultValue={''}>
            {Object.keys(methodMapping).map(key => (
              <option key={key} value={key}>
                {methodMapping[key]}
              </option>
            ))}
          </SelectField>

          <AsyncButton
            text="Ok"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleSubmit}
          />
        </Form>
      </BaseModal>
    );
  }
}
