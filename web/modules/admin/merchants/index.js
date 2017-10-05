import React, { Component } from 'react';
import Form from 'ui/Form';
import { AdminTable as Table } from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';

var defaultState = {
  filters: {
    account_status: 'activated',
  },
};

export default class MerchantList extends Component {
  state = defaultState;

  onSubmit = filters => this.setState({ filters });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Merchant List</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
            <SelectField
              name="account_status"
              label="Status"
              defaultValue={defaultState.filters.account_status}
            >
              <option value="">All</option>
              <option value="activated">Activated</option>
            </SelectField>
            <Field name="sub_accounts" label="Linked-accounts for ID" />
            <CheckField label="Linked Accounts Only" name="sub_accounts" />
            <button>Apply</button>
          </Form>
        </div>
        <Table
          route="admin_fetch_merchants_new"
          queryParams={this.state.filters}
        />
      </div>
    );
  }
}
