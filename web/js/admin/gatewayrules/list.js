import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'util/collection';
import { adminFetch } from 'util/fetch';
import { replaceSlider } from 'common/modal';

export default class PlanList extends Component {
  collection = new Collection({
    fetchRoute: 'admin_fetch_entity_multiple',
    fetchFn: adminFetch,
    urlParams: {
      type: 'gateway_rule',
    },
  });

  onSubmit = filters => this.collection.filters.set(filters);

  render() {
    var filters = this.collection.filters;
    return (
      <div class="list-container">
        <div class="box">
          <header>Gateway Rules</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="merchant_id" label="Merchant ID" />
            <button>Search</button>
          </Form>
        </div>
        <Table model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [
  ['Plan ID', item => item.id],
  ['Plan Name', item => item.name],
  ['Number of Rules', item => item.count],
];
