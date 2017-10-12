import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';
import Collection from 'util/collection';
import { adminFetch } from 'util/fetch';
import { openMerchantEntity } from './entity/entity-resources';

const defaultFilters = {
  account_status: 'activated',
};

export default class MerchantList extends Component {
  collection = new Collection({
    fetchRoute: 'admin_fetch_merchants_new',
    fetchFn: adminFetch,
    filters: defaultFilters,
  });

  onSubmit = filters => this.collection.setFilters(filters);

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
            </SelectField>
            <Field name="sub_accounts" label="Linked-accounts for ID" />
            <CheckField label="Linked Accounts Only" name="sub_accounts" />
            <button>Apply</button>
          </Form>
        </div>
        <Table
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
  ['Referrer', item => item.referrer],
  ['Marketplace Owner', item => item.parent_id],
  ['Status', item => item.count],
  ['Registered At', item => item.created_at],
  ['Submitted At', item => item.merchant_detail.submitted_at],
  ['Tags', item => item.tag_list.join()],
];
