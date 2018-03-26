import React, { Component } from 'react';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import { SelectField, FromField, ToField, CheckField } from 'ui/Field';

import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { featuresAkaMap, showEntity } from './Entity';
import { statusPill } from 'common/data';
import { snakeToTitleCase } from 'common/util';

const defaultFilters = {
  status: '',
};

function fetchFn() {
  let currentFilter = this.filters.status;
  return adminFetch(...arguments).then(data => {
    if (data) {
      return data.items.map(feature => ({
        id: `${feature.merchant_id}_${feature.product}`,
        ...feature,
      }));
    }
  });
}

export default class PublicFeaturesList extends Component {
  collection = new Collection({
    data: {
      url: 'live/merchant/requests',
    },
    fetchFn,
    filters: defaultFilters,
  });

  filter = e => {
    this.collection.addFilters({
      [e.target.name]: e.target.value ? e.target.value : null,
    });
  };

  onSubmit = filters => {
    return this.collection.applyFilters(filters);
  };

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Public Features</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <SelectField
              label="Status"
              name="status"
              onChange={this.filter}
              defaultValue={defaultFilters.status}
            >
              <option value="">All</option>
              {publicFeatureStatuses.map(status => (
                <option value={status} key={status}>
                  {snakeToTitleCase(status)}
                </option>
              ))}
            </SelectField>
            <SelectField label="Product" name="name" onChange={this.filter}>
              <option value="">All</option>
              {Object.keys(featuresAkaMap).map(feature => (
                <option value={feature} key={feature}>
                  {featuresAkaMap[feature]}
                </option>
              ))}
            </SelectField>
            <FromField format="X" allowToday={true} />
            <ToField format="X" allowToday={true} />
            <button class="pull-right">Apply</button>
          </Form>
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
  ['Merchant ID', item => item.merchant.id],
  ['Merchant Name', item => item.merchant.name],
  ['Product', item => item.name],
  ['Account Activation Status', item => item.merchant.activated.toString()],
  ['Product Activation Status', item => statusPill(item.status)],
];

const publicFeatureStatuses = [
  'under_review',
  'needs_clarification',
  'activated',
  'rejected',
];
