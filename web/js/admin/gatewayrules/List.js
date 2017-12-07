import React, { Component } from 'react';
import { observer } from 'mobx-react';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, SelectMode } from 'ui/Field';
import { merchantId } from 'ui/Item';
import AsyncButton from 'ui/AsyncButton';

import { showEntity } from './Entity';
import { replaceSlider } from 'common/modal';
import Collection from 'model/collection';
import Model from './model';

import { adminFetch } from 'util/fetch';
import { methods, gateways } from 'util/data';

const defaultFilters = {
  merchant_id: '100000Razorpay',
};

@observer
export default class GatewayRuleList extends Component {
  state = {
    selectedType: '',
  };
  collection = new Collection({
    data: {
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'gateway_rule',
      },
      mode: 'test',
    },
    model: Model,
    filters: defaultFilters,
    fetchFn: adminFetch,
  });

  handleTypeChange = e => {
    this.setState({
      selectedType: e.target.value,
    });
  };

  onSubmit = filters => {
    this.collection.data.mode = filters.mode;
    delete filters.mode;
    this.collection.applyFilters(filters);
  };

  render() {
    var filters = this.collection.filters;
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Gateway Rules
            <div
              class="btn pull-right"
              onClick={showEntity.bind(null, this.collection)}
            >
              Add a Rule
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field
              name="merchant_id"
              label="Merchant ID"
              defaultValue={defaultFilters.merchant_id}
            />
            <SelectField
              name="type"
              label="Type"
              value={this.state.selectedType}
              onChange={this.handleTypeChange}
            >
              <option value="">All</option>
              <option value="sorter">Sorter</option>
              <option value="filter">Filter</option>
            </SelectField>
            {this.state.selectedType === 'filter' && (
              <SelectField name="filter_type" label="Filter">
                <option value="">All</option>
                <option value="select">Select</option>
                <option value="reject">Reject</option>
              </SelectField>
            )}
            <Field name="group" label="Group" />
            <SelectField name="method" label="Method">
              <option value="">All</option>
              {Object.keys(methods).map((method, index) => (
                <option key={index} value={method}>
                  {methods[method]}
                </option>
              ))}
            </SelectField>
            <SelectMode defaultValue={defaultFilters.mode} />
            <button>Search</button>
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
  ['Rule Id', item => item.id],
  ['Merchant Id', merchantId],
  [
    'Type',
    item => (
      <span
        class={`pill ${
          item.type === 'filter' ? 'label-yellow' : 'label-primary'
        }`}
      >
        {item.type}
      </span>
    ),
  ],
  [
    'Load/Filter',
    item =>
      item.type === 'sorter' ? (
        item.load
      ) : (
        <span
          class={`pill ${
            item.filter_type === 'select' ? 'label-success' : 'label-danger'
          }`}
        >
          {item.filter_type}
        </span>
      ),
  ],
  ['Group', item => item.group],
  ['Method', item => methods[item.method]],
  ['Gateway', item => gateways[item.method][item.gateway]],
  [
    'Action',
    item => (
      <AsyncButton
        text="Delete"
        class="link danger"
        pendingClass="small spinner"
        onClick={item.delete}
      />
    ),
  ],
];
