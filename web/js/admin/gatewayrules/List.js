import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { SelectField, SelectMode } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { replaceSlider } from 'common/modal';
import { methods } from 'util/data';

export default class GatewayRuleList extends Component {
  collection = new Collection({
    data: {
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'gateway_rule',
      },
    },
    fetchFn: adminFetch,
    items: [],
  });

  onSubmit = ({ mode, ...filters }) => {
    this.collection.data.mode = mode;
    return this.collection.setFilters(filters);
  };

  render() {
    var filters = this.collection.filters;
    return (
      <div class="list-container">
        <div class="box">
          <header>Gateway Rules</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="merchant_id" label="Merchant ID" required />
            <SelectField name="type" label="Type">
              <option value="">All</option>
              <option value="sorter">Sorter</option>
              <option value="filter">Filter</option>
            </SelectField>
            <Field name="group" label="Group" />
            <SelectField name="filter_type" label="Filter">
              <option value="">All</option>
              <option value="select">Select</option>
              <option value="reject">Reject</option>
            </SelectField>
            <SelectField name="method" label="Method">
              <option value="">All</option>
              {Object.keys(methods).map((method, index) => (
                <option key={index} value={method}>
                  {methods[method]}
                </option>
              ))}
            </SelectField>
            <SelectMode />
            <button>Search</button>
          </Form>
        </div>
        <Table model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [
  ['Rule Id', item => item.id],
  ['Merchant Id', item => item.merchant_id],
  ['Type', item => item.type],
  ['Group', item => item.group],
  ['Gateway', item => item.gateway_acquirer],
  ['Method', item => methods[item.method]],
  ['Load', item => item.load],
  ['Filter Type', item => item.filter_type],
];
