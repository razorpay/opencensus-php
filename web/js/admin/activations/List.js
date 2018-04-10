import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import user from 'admin/user';

import { notifySuccess, notifyError, closeModal } from 'common/modal';
import { statusPill } from 'common/data';
import { formatDate, prevent } from 'common/util';
import { openModal } from 'common/modal';
import { adminFetch, adminPost } from 'common/fetch';
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
  account_status: 'pending_under_review',
};

export default class MerchantList extends Component {
  state = {
    accountStatus: defaultFilters.account_status,
    selectedMerchants: [],
    pending: true,
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
        this.reviewers = [{ id: '', name: 'Unassigned' }, ...response];
        this.setState({ pending: false });
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
        'Reviewer',
        item => (
          <SearchableSelectField
            name="reviewer_id"
            options={this.reviewers}
            trackBy="id"
            defaultValue={item.merchant_detail.reviewer_id || ''}
            onChange={this.handleReviewerAssignment.bind(item)}
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
      ['Name', item => item.name],
      ['Email', item => item.email],
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

  handleReviewerAssignment = ({ option }) => {
    let body = {
      //TODO: remove admin_
      reviewer_id: 'admin_' + option.id,
      merchants: [this.id],
    };
    adminPost({
      url: 'live/merchant/activation/bulk_assign_reviewer',
      data: body,
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchants assigned successfully');
          closeModal();
        }
      })
      .catch(err => notifyError(JSON.stringify(err.response)));
  };

  handleBulkAssign = e => {
    //prevent filter form submission
    prevent(e);
    openModal(
      <BulkAssign
        reviewers={this.reviewers}
        selectedMerchants={this.state.selectedMerchants}
      />
    );
  };

  render() {
    const { selectedMerchants } = this.state;

    if (this.state.pending) {
      return <div class="table-pending" />;
    }

    return (
      <div class="list-container activations">
        <div class="box">
          <header>Merchant List</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />

            <SelectField name="account_status" label="Activation Status">
              <option value="pending_under_review">Under Review</option>
              <option value="pending_needs_clarification">
                Needs Clarification
              </option>
            </SelectField>
            <Field name="sub_accounts" label="Linked-accounts for ID" />
            <CheckField
              label="Linked Accounts Only"
              name="sub_accounts"
              defaultChecked={''}
            />
            <FromField />
            <ToField />
            <div style={{ width: '172px' }}>
              <SearchableSelectField
                label="Reviewer"
                name="reviewer_id"
                options={this.reviewers}
                trackBy="id"
                defaultValue={''}
              />
            </div>
            <button class="pull-right">Apply</button>
            <button
              class={`pull-left btn-default${
                !selectedMerchants.length ? ' disabled' : ''
              }`}
              disabled={selectedMerchants.length === 0}
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
