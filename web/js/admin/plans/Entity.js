import React, { Component } from 'react';
import { openSlider } from 'common/modal';
import Duplexes from 'ui/Duplexes';
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

  render() {
    let { props, items, save, updateName } = this.collection;
    if (!sharedNetworks.get()) {
      return <div class="spinner" />;
    }
    return (
      <div>
        <header>
          {(props.id && props.name) || (
            <div>
              Enter Plan Name: <Field onChange={updateName} />
              {items.length > 1 && (
                <AsyncButton
                  class="btn"
                  pendingClass="btn spinner"
                  onClick={save}
                  text="Save Plan"
                />
              )}
            </div>
          )}
        </header>
        <Duplexes model={this.collection} fields={fields} />
      </div>
    );
  }
}

// options.feature[item.feature]
const namedKey = (item, name, values = options[name]) => {
  var value = item[name] || '';
  var displayValue = value;
  if (typeof values === 'object') {
    displayValue = values[value];
  }

  if (item.id || item.readonly) {
    return displayValue;
  } else if (typeof values === 'string') {
    return <input name={name} value={value} onChange={item.onPropChange} />;
  } else {
    return (
      <select name={name} value={value} onChange={item.onPropChange}>
        {Object.keys(values).map(value => (
          <option value={value} key={value}>
            {values[value]}
          </option>
        ))}
      </select>
    );
  }
};

const fields = [
  item => item.id && ['', item.id],
  item => ['Feature', namedKey(item, 'feature')],
  item => item.feature && ['Method', namedKey(item, 'payment_method')],
  item =>
    item.feature === 'payment' &&
    item.isCard && ['Card Type', namedKey(item, 'payment_method_type')],

  item => [
    'Network',
    namedKey(
      item,
      'payment_network',
      sharedNetworks.get()[item.payment_method] || options.payment_method
    ),
  ],
  item => ['Issuer', namedKey(item, 'payment_issuer')],
  item => ['International', namedKey(item, 'international')],
  item => [
    'Action',
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
        onClick={item.save}
      />
    ),
  ],
];

export function openPricingEntity() {
  openSlider(<PlanEntity plan={this} />);
}
