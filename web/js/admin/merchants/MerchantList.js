import React, { Component } from 'react';

import { statusPill } from 'util/data';
import { formatDate } from 'util/index';
import { adminFetch } from 'util/fetch';
import { openMerchantEntity } from './entity/entity-resources';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, SwitchField } from 'ui/Field';
import Collection from 'model/collection';

const defaultFilters = {
  account_status: 'pending',
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

    //hijack account_status based on activation_status value
    if (filters.account_status === 'pending') {
      filters.account_status = filters.activation_status;
      delete filters.activation_status;
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
              label="Account Status"
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
              <SelectField name="activation_status" label="Activation Status">
                <option value="pending">All</option>
                <option value="pending_under_review">Under Review</option>
                <option value="pending_needs_clarification">
                  Needs Clarification
                </option>
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
  ['Referrer', item => item.referrer || '--'],
  ['Name', item => item.name],
  ['Email', item => item.email],
  [
    'Activation Progress',
    item => (
      <span
        class={`pill ${
          item.merchant_detail.activation_progress < 100
            ? 'label-danger'
            : 'label-success'
        }`}
      >
        {item.merchant_detail.activation_progress}%
      </span>
    ),
  ],
  [
    'Activation Status',
    item => statusPill(item.merchant_detail.activation_status),
  ],
  ['Registered At', item => formatDate(item.created_at)],
  [
    'Submitted At',
    item =>
      item.merchant_detail.submitted_at
        ? formatDate(item.merchant_detail.submitted_at)
        : '--',
  ],
  ['Tags', item => item.tag_list.join(', ')],
];
