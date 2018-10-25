import React, { Component } from 'react';
import { openModal, closeModal } from 'common/modal';
import { DataTable } from 'ui/Table';
import Plan, { options } from './plan';
import { toJS, observable } from 'mobx';
import { observer } from 'mobx-react';
import * as item from 'ui/Item';
import AsyncButton from 'ui/AsyncButton';
import Field from 'ui/Field';
import { ModalContent } from 'component/Modal';
import { adminFetch } from 'common/fetch';

let sharedNetworks = observable.shallowBox();

@observer
export default class PlanEntity extends Component {
  collection = new Plan(this.props.plan);

  componentWillMount() {
    adminFetch('live/pricing/networks').then(networks => {
      networks.netbanking = networks.bank;
      networks.emi = networks.card;
      sharedNetworks.set(networks);
    });
  }

  save = _ => {
    this.collection.save().then(data => data && closeModal());
  };

  copyPlan = () => {
    this.props.plan.collection.data.copyItem(toJS(this.collection.items));
  };

  render() {
    let { props, items, updateName, pending } = this.collection;
    pending = pending.fetch;

    let isLoading = !sharedNetworks.get();

    return (
      <ModalContent
        class="pricing-container"
        header={
          props.id && props.name ? (
            <div>
              <span>{props.name}</span>
              {!pending && (
                <button class="btn" onClick={this.copyPlan}>
                  Clone Plan
                </button>
              )}
            </div>
          ) : (
            <div class="pricing-header">
              <Field
                label="Enter Plan Name:"
                placeholder="Enter Plan name"
                onChange={updateName}
                defaultValue={props.name}
                required
              />
              {items.length > 1 && (
                <AsyncButton
                  class="btn"
                  pendingClass="btn spinner"
                  onClick={this.save}
                  text="Save Plan"
                  disabled={isLoading}
                />
              )}
            </div>
          )
        }
      >
        {isLoading ? (
          <div class="spinner center" />
        ) : (
          <DataTable
            animateRow={false}
            pending={pending}
            items={this.collection.items}
            fields={fields}
          />
        )}
      </ModalContent>
    );
  }
}

const fields = [
  ['', item => item.id],
  ['Feature', item => item.selectField('feature')],
  [
    'Method',
    item => (
      <div>
        {item.selectField('payment_method')}
        {item.paymentMethodTypeField()}
        {item.receiverTypeField()}
        {item.authTypeField()}
        {item.internationalField()}
        {item.emiDurationField()}
      </div>
    ),
  ],
  [
    'Network',
    item =>
      item.selectField('payment_network', {
        ...options.payment_network,
        ...sharedNetworks.get()[item.payment_method],
      }) || 'Any',
  ],
  ['Issuer', item => item.issuerField() || 'Any'],
  [
    'Amount Range (Paisa)',
    item => (
      <div>
        {item.selectField('amount_range') || 'None'}
        {item.customRangeField()}
      </div>
    ),
  ],
  [
    'Rate (%)',
    item =>
      item.numberField('percent_rate', {
        max: 100,
      }),
  ],
  ['Fee (₹)', item => item.numberField('fixed_rate')],
  ['Min Fee', item => item.numberField('min_fee')],
  ['Max Fee', item => item.numberField('max_fee')],
  [
    'Action',
    item =>
      ((item.id || item.readonly) &&
        (item.isEditing ? (
          <div>
            <AsyncButton
              class="link"
              pendingClass="spinner"
              onClick={item.update}
              text="Save"
            />
            <span class="link danger" onClick={item.cancelEditHandler}>
              Cancel
            </span>
          </div>
        ) : (
          <div>
            {item.id && (
              <span class="link" onClick={item.editRuleHandler}>
                Edit
              </span>
            )}
            <AsyncButton
              class="link danger"
              pendingClass="spinner"
              text="Delete"
              onClick={item.delete}
            />
          </div>
        ))) || (
        <AsyncButton
          class="btn"
          text="Add"
          pendingClass="spinner"
          onSubmit={item.save}
        />
      ),
  ],
];

export function openPricingEntity() {
  openModal(<PlanEntity plan={this} />);
}

export function copyPricingEntity(rules) {
  let copiedPlan = {
    collection: this.collection,
    id: null,
    items: [],
  };

  let plan_id;

  copiedPlan.items = rules.map(rule => {
    const {
      id,
      plan_name,
      created_at,
      updated_at,
      deleted_at,
      expired_at,
      payment_network_name,
      ...data
    } = rule;

    plan_id = rule.plan_id;
    delete data.plan_id;

    data.international = data.international | 0;
    ['fixed_rate', 'percent_rate', 'min_fee', 'max_fee'].forEach(elem => {
      if (data[elem] || parseInt(data[elem]) === 0) {
        data[elem] *= 100;
      }
    });

    if (id) {
      data.readonly = true;
    }
    return data;
  });

  closeModal();
  openModal(<PlanEntity key={plan_id || 'new_plan'} plan={copiedPlan} />);
}
