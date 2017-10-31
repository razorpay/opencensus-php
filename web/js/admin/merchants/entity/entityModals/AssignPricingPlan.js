import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';

export default class PricingPlanModal extends Component {
  state = { pricingPlans: {} };

  componentWillMount() {
    adminFetch({
      route_name: 'pricing_get_merchant_plans',
    }).then(data => {
      const pricingPlans = {};

      for (let key in data.items) {
        let value = data.items[key];
        pricingPlans[value.id] = value.name;
      }

      this.setState({ pricingPlans });
    });
  }

  handleConfirm = body => {
    const { props } = this.props;

    return confirm(
      'Any previously assigned plan for the merchant will be replace with selected.',
      () => {
        closeModal();
        const pricingData = {
          pricing_plan_id: body.pricing_plan_id,
          pricing_plan_name: this.state.pricingPlans[body.id],
        };

        return adminPost({
          route_name: 'merchant_assign_pricing',
          url_params: {
            id: props.details.id,
          },
          body: pricingData,
        })
          .then(response => {
            if (response.data.success) {
              notifySuccess('Pricing Plan assigned successfully.');
              closeModal();
            } else {
              response.data.errors.map(error => notifyError(error));
            }
          })
          .catch(err => {
            notifyError(JSON.stringify(err.response));
          });
      },
      'Submit',
      'Cancel'
    );
  };

  render() {
    return (
      <BaseModal header="Assign Pricing Plan">
        <span>
          <strong>
            Warning: The assigned pricing plan will replace the current pricing
            plan and affect all future test/live transactions.
          </strong>
        </span>

        <Form>
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
