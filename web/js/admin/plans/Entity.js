import React, { Component } from 'react';
import { openModal, closeModal } from 'common/modal';
import { DataTable } from 'ui/Table';
import Plan, { options } from './plan';
import { observable } from 'mobx';
import { observer } from 'mobx-react';
import * as item from 'ui/Item';
import AsyncButton from 'ui/AsyncButton';
import Field from 'ui/Field';
import { adminFetch } from 'util/fetch';

let sharedNetworks = observable.shallowBox();

@observer
export default class PlanEntity extends Component {
  collection = new Plan(this.props.plan);

  componentWillMount() {
    adminFetch('pricing_supported_networks').then(networks => {
      networks.netbanking = networks.bank;
      networks.emi = networks.card;
      sharedNetworks.set(networks);
    });
  }

  save = _ => {
    this.collection.save().then(data => data && closeModal());
  };

  render() {
    let { props, items, updateName, pending } = this.collection;
    pending = pending.fetch;

    if (!sharedNetworks.get()) {
      return <div class="spinner" />;
    }
    return (
      <div class="pricing-container">
        <header>
          {(props.id && props.name) || (
            <div>
              Enter Plan Name: <Field onChange={updateName} />
              {items.length > 1 && (
                <AsyncButton
                  class="btn"
                  pendingClass="btn spinner"
                  onClick={this.save}
                  text="Save Plan"
                />
              )}
            </div>
          )}
        </header>
        <DataTable
          pending={pending}
          items={this.collection.items}
          fields={fields}
        />
      </div>
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
      }),
  ],
  ['Issuer', item => item.selectField('payment_issuer')],
  ['Amount Range', item => item.selectField('amount_range')],
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
      ((item.id || item.readonly) && (
        <AsyncButton
          class="link danger"
          pendingClass="spinner"
          text="Delete"
          onClick={item.delete}
        />
      )) || (
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
