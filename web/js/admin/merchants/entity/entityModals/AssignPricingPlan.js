import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SearchableSelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import { isWorkflow } from 'common/util';

export default class PricingPlanModal extends Component {
  state = { pricingPlans: {}, pending: true };

  componentWillMount() {
    adminFetch('pricing/merchants').then(data => {
      const pricingPlans = {};

      for (let key in data) {
        let value = data[key];
        pricingPlans[value.plan_id] = value.plan_name;
      }

      this.setState({ pricingPlans, pending: false });
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
        url: `merchants/${this.props.merchantId}/pricing`,
        data: pricingData,
      })
        .then(data => {
          if (data) {
            closeModal();

            if (isWorkflow(data)) {
              notifySuccess('Workflow is created successfully.');
              return;
            }
            notifySuccess('Pricing Plan assigned successfully.');
          }
        })
        .catch(err => {
          notifyError(JSON.stringify(err.response));
        });
    });
  };

  render() {
    const pricingPlanId = this.props.props.merchant.details.pricing_plan_id;

    return (
      <BaseModal header="Assign Pricing Plan">
        <Form class="full-span full-elements" style={{ width: '450px' }}>
          <div class="m-b">
            <strong>
              Warning: The assigned pricing plan will replace the current
              pricing plan and affect all future test/live transactions.
            </strong>
          </div>
          {this.state.pending ? (
            <Field label="Plans to be Assigned" value="Loading..." disabled />
          ) : (
            <SearchableSelectField
              trackBy="value"
              name="pricing_plan_id"
              defaultValue={pricingPlanId || '1In3Yh5Mluj605'}
              label="Plans to be Assigned"
              options={Object.keys(this.state.pricingPlans).map(key => ({
                name: this.state.pricingPlans[key],
                value: key,
              }))}
            />
          )}

          <AsyncButton
            text="Ok"
            class="btn"
            pendingClass="small spinner"
            disabled={this.state.pending}
            onSubmit={this.handleConfirm}
          />
        </Form>
      </BaseModal>
    );
  }
}
