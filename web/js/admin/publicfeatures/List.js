import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import { SelectField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

const defaultFilters = {
  status: 'pending',
};

const features = [
  'marketplace_activation_status',
  'subscriptions_activation_status',
  'virtual_accounts_activation_status',
];

const featureNames = {
  marketplace_activation_status: 'Route',
  subscriptions_activation_status: 'Subscription',
  virtual_accounts_activation_status: 'Smart Collect',
};

function fetchFn() {
  let currentFilter = this.filters.status;
  adminFetch(...arguments).then(data => {
    if (data) {
      data = data.reduce((rows, current) => {
        features.forEach(
          f =>
            current[f] === currentFilter &&
            rows.push({
              merchant_id: current.merchant_id,
              contact_name: current.contact_name,
              feature: featureNames[f],
            })
        );
        return rows;
      }, []);
      return data;
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

  filter = e => this.collection.setFilters({ status: e.target.value });

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
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </SelectField>
          </div>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [
  ['Merchant ID', item => item.merchant_id],
  ['Feature', item => item.feature],
  ['Contact', item => item.contact_name],
];
