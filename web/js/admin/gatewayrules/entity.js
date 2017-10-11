import React, { Component } from 'react';
import Table from 'ui/Table';
import Collection from 'util/collection';
import { observer } from 'mobx-react';

@observer
export default class PlanEntity extends Component {
  collection = new Collection({
    items: this.props.model.rules,
  });

  componentWillReceiveProps(props) {
    if (this.props.model.id !== props.model.id) {
      this.collection.items.replace(props.model.rules);
    }
  }

  render() {
    return (
      <div>
        <Table model={this.collection} fields={fields} />
      </div>
    );
  }
}

const PricingFeature = item => (
  <select value={item.feature} readOnly>
    <option value="payment">Payment</option>
    <option value="recurring">Recurring</option>
    <option value="payout">Payout</option>
    <option value="transfer">Transfer</option>
    <option value="emi">EMI</option>
  </select>
);

const fields = [
  ['Rule ID', item => item.id],
  ['Pricing Feature', PricingFeature],
];
