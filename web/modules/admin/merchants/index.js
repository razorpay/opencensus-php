import React, { Component } from 'react';
import Form from 'ui/Form';
import { AdminTable as Table } from 'ui/Table';
import Field, { Select } from 'ui/Field';

export default class MerchantList extends Component {
  state = {
    filters: {},
  };

  onSubmit = filters => this.setState({ filters });

  render() {
    return (
      <div>
        <Form onSubmit={this.onSubmit}>
          <Field name="q" label="Search" />
          <Select name="account_status" label="Status" defaultValue="activated">
            <option value="">All</option>
            <option value="activated">Activated</option>
          </Select>
          <Field
            type="checkbox"
            label="Linked Accounts Only"
            name="sub_accounts"
          />
          <Field name="sub_accounts" label="Linked-accounts for ID" />
          <button>Go</button>
        </Form>
        <Table
          route="admin_fetch_merchants_new"
          query_params={this.state.filters}
        />
      </div>
    );
  }
}
