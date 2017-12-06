import React, { Component } from 'react';

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
              defaultValue={defaultFilters.account_status}
            >
              <option value="">All</option>
              <option value="activated">Activated</option>
              <option value="pending">Pending Activation</option>
              <option value="dead">Dead</option>
              <option value="archived">Archived</option>
              <option value="suspended">Suspended</option>
            </SelectField>
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
  ['Referrer', item => item.referrer || '--'],
  ['Marketplace Owner', item => item.parent_id || '--'],
  [
    'Activated',
    item => (
      <i
        class={`i ${
          item.activated_at ? 'i-yes  text-success' : 'i-no text-danger'
        }`}
      />
    ),
  ],
  ['Registered At', item => formatDate(item.created_at)],
  [
    'Submitted At',
    item =>
      item.submitted_at ? formatDate(item.merchant_detail.submitted_at) : '--',
  ],
  ['Tags', item => item.tag_list.join()],
];
