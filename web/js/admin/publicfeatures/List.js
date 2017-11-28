import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import { SelectField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { showEntity } from './Entity';

const defaultFilters = {
  status: 'pending',
};

const featureNames = {
  marketplace_activation_status: 'Marketplace',
  subscriptions_activation_status: 'Subscriptions',
  virtual_accounts_activation_status: 'Virtual Accounts',
};

function fetchFn() {
  let currentFilter = this.filters.status;
  return adminFetch(...arguments).then(data => {
    if (data) {
      return data.map(feature => ({
        id: `${feature.merchant_id}_${feature.product}`,
        ...feature,
      }));
    }
  });
}

export default class PublicFeaturesList extends Component {
  collection = new Collection({
    data: {
      route_name: 'onboarding_features_fetch_submissions',
    },
    fetchFn,
    filters: defaultFilters,
  });

  filter = e => this.collection.applyFilters({ status: e.target.value });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Public Features</header>
          <div class="filters">
            <SelectField
              label="Status"
              name="status"
              onChange={this.filter}
              defaultValue={defaultFilters.status}
            >
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </SelectField>
          </div>
        </div>
        <PageTable
          model={this.collection}
          fields={fields}
          onClick={showEntity}
        />
      </div>
    );
  }
}

const fields = [
  ['Merchant ID', item => item.merchant_id],
  ['Product', item => item.product],
  ['Status', item => item.status],
];
