import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, SwitchField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { openMerchantEntity } from './entity/entity-resources';
import { formatDate } from 'util/index';

const defaultFilters = {
  account_status: 'pending',
  activation_status: 'under_review',
};

export default class MerchantList extends Component {
  state = {
    accountStatus: defaultFilters.account_status,
  };
  collection = new Collection({
    data: {
      route_name: 'admin_fetch_merchants_new',
    },
    fetchFn: adminFetch,
    filters: defaultFilters,
  });

  onSubmit = filters => {
    if (filters['sub_accounts'] == 0) {
      delete filters['sub_accounts'];
    }
    return this.collection.applyFilters(filters);
  };

  handleAccountStatusChange = e => {
    this.setState({ accountStatus: e.target.value });
  };

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
              value={this.state.accountStatus}
              onChange={this.handleAccountStatusChange}
            >
              <option value="">All</option>
              <option value="activated">Activated</option>
              <option value="pending">Pending Activation</option>
              <option value="dead">Dead</option>
              <option value="archived">Archived</option>
              <option value="suspended">Suspended</option>
            </SelectField>
            {this.state.accountStatus === 'pending' && (
              <SelectField
                name="activation_status"
                label="State"
                defaultValue={defaultFilters.activation_status}
              >
                <option value="">All</option>
                <option value="under_review">Under Review</option>
                <option value="needs_clarification">Needs Clarification</option>
              </SelectField>
            )}
            <Field name="sub_accounts" label="Linked-accounts for ID" />
            <SwitchField label="Linked Accounts Only" name="sub_accounts" />
            <button class="pull-right">Apply</button>
          </Form>
        </div>
        <PageTable
          model={this.collection}
          fields={fields}
          onClick={openMerchantEntity}
        />
      </div>
    );
  }
}

const fields = [
  ['Merchant ID', item => item.id],
  ['Name', item => item.name],
  ['Email', item => item.email],
  [
    'Activation Progress',
    item => (
      <span class="pills label-success">{`${
        item.merchant_detail.activation_progress
      } %`}</span>
    ),
  ],
  ['Activation Status', item => item.merchant_detail.activation_status],
  ['Registered At', item => formatDate(item.created_at)],
  ['Submitted At', item => formatDate(item.merchant_detail.submitted_at)],
  [
    'Tags',
    item =>
      item.tag_list.map((tag, idx) => (
        <span class="pills label-muted" key={idx}>
          {tag}
        </span>
      )),
  ],
];
