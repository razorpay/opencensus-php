import React, { Component } from 'react';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, FromField, ToField, CheckField } from 'ui/Field';

import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { showEntity } from './Entity';
import { statusPill, publicFeature } from 'common/data';
import { snakeToTitleCase, prevent, formatDate } from 'common/util';

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

  fields = [
    [
      'Merchant ID',
      item => (
        <span
          class="link"
          onClick={this._openMerchantDetails}
          data-merchantid={item.merchant.id}
        >
          {item.merchant.id}
        </span>
      ),
    ],
    ['Merchant Name', item => item.merchant.name],
    ['Product', item => item.name],
    [
      'Account Activation Status',
      item => (item.merchant.activated ? 'Activated' : 'Not Activated'),
    ],
    ['Product Activation Status', item => statusPill(item.status)],
    ['Submitted At', item => formatDate(item.created_at)],
  ];

  onSubmit = filters => {
    return this.collection.applyFilters(filters);
  };

  _openMerchantDetails = event => {
    //prevent default row click for merchant id links
    prevent(event);

    const merchantId = event.target.dataset.merchantid;

    window.open(`/admin/merchants/${merchantId}`, '_blank');
  };

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Public Features</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field label="Merchant ID" name="merchant_id" />
            <SelectField
              label="Status"
              name="status"
              onChange={this.filter}
              defaultValue={defaultFilters.status}
            >
              <option value="">All</option>
              {publicFeature.statuses.map(status => (
                <option value={status} key={status}>
                  {snakeToTitleCase(status)}
                </option>
              ))}
            </SelectField>
            <SelectField label="Product" name="name" onChange={this.filter}>
              <option value="">All</option>
              {Object.keys(publicFeature.featuresAkaMap).map(feature => (
                <option value={feature} key={feature}>
                  {publicFeature.featuresAkaMap[feature]}
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
          fields={this.fields}
          onClick={showEntity}
        />
      </div>
    );
  }
}
