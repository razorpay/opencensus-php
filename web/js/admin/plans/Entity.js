import React, { Component } from 'react';
import { replaceSlider } from 'common/modal';
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

export function openPricingEntity() {
  replaceSlider(<PlanEntity model={this} />);
}

/*{
    "id": "1b03fh9jXGH34f",
    "plan_id": "2atGxLIYLyHWg7",
    "plan_name": "Startup Plan",
    "feature": "payment",
    "gateway": null,
    "payment_method": "wallet",
    "payment_method_type": null,
    "payment_network": null,
    "payment_issuer": null,
    "emi_duration": null,
    "international": false,
    "amount_range_active": false,
    "amount_range_min": null,
    "amount_range_max": null,
    "percent_rate": 250,
    "fixed_rate": 0,
    "min_fee": 0,
    "max_fee": null,
    "created_at": 1505747346,
    "updated_at": 1505747346,
    "deleted_at": null,
    "expired_at": null
}*/
