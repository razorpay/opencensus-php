import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import { isWorkflow } from 'common/util';

const methodMapping = {
  '': 'All',
  card: 'Card',
  netbanking: 'Netbanking',
  upi: 'UPI',
  emi: 'EMI',
  wallet: 'Wallet',
  bank_transfer: 'Bank Transfer',
};

const type_list = { Settlement: 'settlement' };

export default class ScheduleModal extends Component {
  state = { settlementPlans: {}, pending: true };

  getCurrentSchedule(currentMethod) {
    let defaultScheduleId = '30000000000000';
    const scheduleTasks = this.props.props.merchant.scheduleTasks;
    const currentScheduleTask = scheduleTasks.find(
      task => task.method === currentMethod
    );
    return currentScheduleTask
      ? currentScheduleTask.schedule_id
      : defaultScheduleId;
  }

  componentWillMount() {
    adminFetch('settlements/schedules').then(data => {
      const settlementPlans = {};

      for (let key in data.items) {
        let value = data.items[key];
        settlementPlans[value.id] = value.name;
      }

      this.setState({
        settlementPlans,
        pending: false,
        defaultSchedule: this.getCurrentSchedule(null),
      });
    });
  }

  handleSubmit = body => {
    const schedulePlanData = {
      method: body.method,
      schedule_id: body.schedule_id,
      type: body.type,
    };

    return adminPost({
      url: `live/merchants/${this.props.merchantId}/schedules`,
      data: schedulePlanData,
    })
      .then(response => {
        if (response) {
          closeModal();

          if (isWorkflow(response)) {
            notifySuccess('Workflow is created successfully.');
            return;
          }
          notifySuccess('Schedule Plan assigned successfully.');
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  handleMethodChange = event => {
    this.setState({
      defaultSchedule: this.getCurrentSchedule(
        event.currentTarget.value || null
      ),
    });
  };

  handleScheduleChange = event => {
    this.setState({ defaultSchedule: event.currentTarget.value });
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

          <SelectField
            name="method"
            label="Method"
            defaultValue={''}
            onChange={this.handleMethodChange}
          >
            {Object.keys(methodMapping).map(key => (
              <option key={key} value={key}>
                {methodMapping[key]}
              </option>
            ))}
          </SelectField>

          {this.state.pending ? (
            <Field label="Schedules" defaultValue="Loading..." disabled />
          ) : (
            <SelectField
              name="schedule_id"
              label="Schedules"
              value={this.state.defaultSchedule}
              onChange={this.handleScheduleChange}
            >
              {Object.keys(this.state.settlementPlans).map(key => (
                <option key={key} value={key}>
                  {this.state.settlementPlans[key]}
                </option>
              ))}
            </SelectField>
          )}

          <AsyncButton
            text="Save"
            class="btn"
            disabled={this.state.pending}
            pendingClass="small spinner"
            onSubmit={this.handleSubmit}
          />
        </Form>
      </BaseModal>
    );
  }
}
