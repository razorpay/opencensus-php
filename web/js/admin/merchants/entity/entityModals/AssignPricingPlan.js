import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';
import { isWorkflow } from 'util/index';

export default class PricingPlanModal extends Component {
  state = { pricingPlans: {} };

  componentWillMount() {
    adminFetch({
      route_name: 'pricing_get_merchant_plans',
    }).then(data => {
      const pricingPlans = {};

      for (let key in data) {
        let value = data[key];
        pricingPlans[value.plan_id] = value.plan_name;
      }

      this.setState({ pricingPlans });
    });
  }

  handleConfirm = body => {
    return confirm(
      'Any previously assigned plan for the merchant will be replace with selected.',
      'Submit'
    ).then(_ => {
      const pricingData = {
        pricing_plan_id: body.pricing_plan_id,
        pricing_plan_name: this.state.pricingPlans[body.id],
      };

      return adminPost({
        route_name: 'merchant_assign_pricing',
        url_params: {
          id: this.props.merchantId,
        },
        body: pricingData,
      })
        .then(data => {
          if (data) {
            if (isWorkflow(data)) {
              return;
            }
            notifySuccess('Pricing Plan assigned successfully.');
            closeModal();
          }
        })
        .catch(err => {
          notifyError(JSON.stringify(err.response));
        });
    });
  };

  render() {
    return (
      <BaseModal header="Assign Pricing Plan">
        <Form class="full-span full-elements" style={{ width: '450px' }}>
          <div class="m-b">
            <strong>
              Warning: The assigned pricing plan will replace the current
              pricing plan and affect all future test/live transactions.
            </strong>
          </div>
          <SelectField
            name="pricing_plan_id"
            label="Plans to be Assigned"
            defaultValue={''}
          >
            {Object.keys(this.state.pricingPlans).map(key => (
              <option key={key} value={key}>
                {this.state.pricingPlans[key]}
              </option>
            ))}
          </SelectField>

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
