import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';
import Collection from 'util/collection';
import { adminFetch } from 'util/fetch';

export default class MerchantList extends Component {
  collection = new Collection({
    fetchRoute: 'pricing_get_merchant_plans',
    fetchFn: adminFetch,
  });

  onSubmit = filters => this.collection.filters.set(filters);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Pricing Plans</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
        </div>
        <Table
          route="pricing_get_merchant_plans"
          model={this.collection}
          fields={pricingFields}
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
