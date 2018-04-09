import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import user from 'admin/user';
import { statusPill } from 'common/data';
import { formatDate, prevent } from 'common/util';
import { openModal } from 'common/modal';
import { adminFetch } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, {
  SelectField,
  CheckField,
  SearchableSelectField,
  FromField,
  ToField,
} from 'ui/Field';
import Collection from 'model/collection';

import BulkAssign from './BulkAssign';

const defaultFilters = {
  account_status: '',
};

export default class MerchantList extends Component {
  state = {
    accountStatus: defaultFilters.account_status,
    selectedMerchants: [],
    reviewers: null,
  };
  collection = new Collection({
    data: {
      url: 'live/admins/merchants',
    },
    fetchFn: adminFetch,
    filters: defaultFilters,
  });

  componentWillMount() {
    adminFetch('live/merchant/activation/reviewers').then(response => {
      if (response) {
        //TODO: remove admin_
        response.forEach(r => (r.id = r.id.replace('admin_', '')));
        this.setState({ reviewers: response });
      }
    });
  }

  getFields = () => {
    const { selectedMerchants } = this.state;

    let fields = [
      // Add checkbox for multiple selection of merchants
      [
        <CheckField
          defaultChecked={false}
          onChange={this.handleAllMerchantSelection}
        />,
        item => (
          <CheckField
            data-merchantid={item.id}
            checked={selectedMerchants.indexOf(item.id) > -1}
            onChange={this.handleMultipleSelection}
          />
        ),
      ],
      [
        'Merchant ID',
        item => (
          <Link to={`/merchants/${item.id}`}>
            <span class="link">{item.id}</span>
          </Link>
        ),
      ],
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
    return fields;
  };

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

  handleMultipleSelection = event => {
    const selectedMerchants = [...this.state.selectedMerchants];
    const merchandId = event.target.dataset.merchantid;
    const index = selectedMerchants.indexOf(merchandId);

    if (index > -1) {
      selectedMerchants.splice(index, 1);
    } else {
      selectedMerchants.push(merchandId);
    }

    this.setState({ selectedMerchants });
  };

  handleAllMerchantSelection = event => {
    let selectedMerchants = [];
    if (event.target.checked) {
      this.collection.items.map(item => selectedMerchants.push(item.id));
    }

    this.setState({ selectedMerchants });
  };

  handleBulkAssign = e => {
    //prevent filter form submission
    prevent(e);
    openModal(
      <BulkAssign
        reviewers={this.state.reviewers}
        selectedMerchants={this.state.selectedMerchants}
      />
    );
  };

  render() {
    const { reviewers, selectedMerchants } = this.state;

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
            <CheckField
              label="Linked Accounts Only"
              name="sub_accounts"
              defaultChecked={''}
            />
            <FromField />
            <ToField />
            {reviewers && (
              <div style={{ width: '172px' }}>
                <SearchableSelectField
                  label="Reviewer"
                  name="reviewer_id"
                  options={reviewers}
                  trackBy="id"
                  defaultValue="Unassigned"
                />
              </div>
            )}
            <button class="pull-right">Apply</button>
            <button
              class={`pull-left btn-default${
                !selectedMerchants.length ? ' disabled' : ''
              }`}
              onClick={this.handleBulkAssign}
            >
              Bulk Assign
            </button>
          </Form>
        </div>
        <PageTable
          customClass="merchants-list"
          model={this.collection}
          fields={this.getFields()}
        />
      </div>
    );
  }
}

const openLink = function(e) {
  e.preventDefault();
  e.stopPropagation();
  window.open(e.target.href, e.target.target);
};
