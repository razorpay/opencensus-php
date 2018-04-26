import React, { Component } from 'react';

import user from 'admin/user';
import { statusPill } from 'common/data';
import { formatDate } from 'common/util';
import { adminFetch } from 'common/fetch';
import { openMerchantEntity } from './entity/entity-resources';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';
import Collection from 'model/collection';

const defaultFilters = {
  account_status: '',
};

export default class MerchantList extends Component {
  state = {
    accountStatus: defaultFilters.account_status,
  };
  collection = new Collection({
    data: {
      url: 'live/admins/merchants',
    },
    fetchFn: adminFetch,
    filters: defaultFilters,
  });

  /*
  * 1. sub_accounts = 0, fetch results without linked-accounts
  * 2. sub_accounts = 1, fetch results with ONLY linked-accounts
  * 3. sub_accounts = some_id, fetch linked-accounts only for the given merchant id
  * 4. sub_accounts query param doesn't exist, fetch all results
  * */
  onSubmit = filters => {
    if (filters['sub_accounts'] == 0) {
      delete filters['sub_accounts'];
    }

    // Explicitly telling to exclude sub_accounts
    if (filters['sub_accounts_exclude'] == 1) {
      filters['sub_accounts'] = 0;
    } else if (filters['sub_accounts_merc-id']) {
      filters['sub_accounts'] = filters['sub_accounts_merc-id'];
    }

    delete filters['sub_accounts_exclude']; // FE only field name. Actual filter: 'sub_accounts'
    delete filters['sub_accounts_merc-id']; // FE only field name. Actual filter: 'sub_accounts'

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

  /* If 'exclude-LA' checked, then uncheck corresponding field */
  handleExcludeLA = e => {
    const isLAExcluded = e.target.checked;

    if (isLAExcluded) {
      document.getElementsByName('sub_accounts_merc-id')[0].value = '';
      document.getElementsByName('sub_accounts')[0].checked = false;
    }
  };

  /* If value of LA exists either from 'LA-id' or 'only LA' checkbox, uncheck 'exclude-LA' */
  handleIncludeOnlyLA = e => {
    // If 'only LA' checked, empty the 'LA id' field
    if (e.target.checked) {
      // checked is true only when checkbox is checked
      document.getElementsByName('sub_accounts_merc-id')[0].value = '';
      document.getElementsByName('sub_accounts_exclude')[0].checked = false;
    }

    // If event.target is 'LA id' field and has value, uncheck 'only LA'checkbox
    if (e.target.name === 'sub_accounts_merc-id' && e.target.value) {
      document.getElementsByName('sub_accounts')[0].checked = false;
      document.getElementsByName('sub_accounts_exclude')[0].checked = false;
    }
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
            <Field
              name="sub_accounts_merc-id"
              label="Linked-accounts for ID"
              onChange={this.handleIncludeOnlyLA}
            />
            <CheckField
              label="Linked-accounts Only"
              name="sub_accounts"
              onChange={this.handleIncludeOnlyLA}
              defaultChecked={''}
            />

            <CheckField
              label="Exclude Linked-accounts"
              name="sub_accounts_exclude"
              onChange={this.handleExcludeLA}
              defaultChecked={''}
            />

            <button class="pull-right">Search</button>
          </Form>
        </div>
        <PageTable
          customClass="merchants-list"
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
    item =>
      statusPill(item.merchant_detail.activation_status, '') || (
        <span
          class={`pill ${
            item.merchant_detail.activation_progress < 100
              ? 'label-danger'
              : 'label-info'
          }`}
        >
          {item.merchant_detail.activation_progress}%
        </span>
      ),
  ],
  ['Registered At', item => formatDate(item.created_at)],
  [
    'Submitted At',
    item =>
      item.merchant_detail.submitted_at
        ? formatDate(item.merchant_detail.submitted_at)
        : '--',
  ],
  [
    'Tags',
    item =>
      item.tag_list.map(tag => (
        <span class="square-pills label-semi-muted" key={tag}>
          {tag}
        </span>
      )),
  ],
];

if (user.permissions.find(perm => perm === 'view_merchant_stats')) {
  fields.push([
    'Action',
    item =>
      item.activated && (
        <a
          onClick={openLink}
          href={'/admin/stats/' + item.id}
          class="btn"
          target="_blank"
        >
          View Stats
        </a>
      ),
  ]);
}

const openLink = function(e) {
  e.preventDefault();
  e.stopPropagation();
  window.open(e.target.href, e.target.target);
};
