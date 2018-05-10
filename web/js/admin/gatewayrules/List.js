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

import { adminFetch } from 'common/fetch';
import { methods, gateways, categories } from 'common/data';
import { isBlank } from 'common/util';

let gateway_url = 'admin/gateway_rule';

@observer
export default class GatewayRuleList extends Component {
  state = {
    selectedType: '',
    selectedMethod: '',
  };
  // TODO: TEST check what mode to pass
  collection = new Collection({
    data: {
      url: `live/${gateway_url}`, // default mode is live
    },
    extraFields: {
      mode: 'live',
    },
    model: Model,
    fetchFn: adminFetch,
  });

  handleFilterChange = e => {
    const propname = e.target.dataset.propname;

    this.setState({
      [propname]: e.target.value,
    });
  };

  onSubmit = filters => {
    this.collection.extraFields.mode = filters.mode;
    this.collection.data.url = `${filters.mode}/${gateway_url}`;
    delete filters.mode; // mode need to be sent now

    this.collection.applyFilters(filters);
  };

  render() {
    var filters = this.collection.filters;
    const { selectedMethod, selectedType } = this.state;

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
            <Field name="merchant_id" label="Merchant ID" />
            <SelectField
              name="type"
              label="Type"
              value={selectedType}
              data-propname="selectedType"
              onChange={this.handleFilterChange}
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
            <SelectField
              name="method"
              label="Method"
              value={selectedMethod}
              data-propname="selectedMethod"
              onChange={this.handleFilterChange}
            >
              <option value="">All</option>
              {Object.keys(methods).map((method, index) => (
                <option key={index} value={method}>
                  {methods[method]}
                </option>
              ))}
            </SelectField>
            {!isBlank(selectedMethod) && (
              <SelectField name="gateway" label="Gateway">
                {Object.keys(gateways[selectedMethod]).map(gateway => (
                  <option value={gateway} key={gateway}>
                    {gateways[selectedMethod][gateway]}
                  </option>
                ))}
              </SelectField>
            )}

            <SelectField name="category2" label="Category2">
              <option value="">All</option>
              {Object.keys(categories).map(category => (
                <option value={category} key={category}>
                  {categories[category]}
                </option>
              ))}
            </SelectField>

            <Field name="network_Category" label="Network Category" />

            <SelectField name="shared_terminal" label="Shared Terminal">
              <option value="">All</option>
              <option value="0">No</option>
              <option value="1">Yes</option>
            </SelectField>

            <SelectMode defaultValue={'live'} />
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
