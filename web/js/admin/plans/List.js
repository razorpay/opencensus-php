import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { openPricingEntity } from './Entity';
import Plan from './plan';

export default class PlanList extends Component {
  collection = new Collection({
    data: {
      route_name: 'pricing_get_merchant_plans',
    },
    fetchFn: adminFetch,
    model: (collection, item) => {
      item.collection = collection;
      return item;
    },
  });

  onSubmit = filters => this.collection.setFilters(filters);

  newPricingEntity = e =>
    openPricingEntity.call({
      collection: this.collection,
    });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Pricing Plans
            <div class="btn" onClick={this.newPricingEntity}>
              Add New
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
        </div>
        <PageTable
          model={this.collection}
          fields={pricingFields}
          onClick={openPricingEntity}
        />
      </div>
    );
  }
}

const pricingFields = [
  ['Plan ID', item => item.id],
  ['Plan Name', item => item.name],
  ['Number of Rules', item => item.count],
];
