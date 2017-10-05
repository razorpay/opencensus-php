import React, { Component } from 'react';
import Form from 'ui/Form';
import { AdminTable as Table } from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';

var defaultState = {
  filters: {},
};

export default class MerchantList extends Component {
  state = defaultState;

  onSubmit = filters => this.setState({ filters });

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
          queryParams={this.state.filters}
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
