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
  count: 20,
  skip: 0,
  reviewer_id: '',
};

function fetchMerchants() {
  return adminFetch(...arguments).then(response => {
    if (response) {
      //update merchantReviewerMap when filters are apllied.
      this._updateMerchateReviewerMap(this.reviewers, response.items);
      return response;
    }
  });
}

export default class MerchantList extends Component {
  state = {
    selectedMerchants: [],
    merchantReviewerMap: {},
    pending: true,
  };

  collection = null;

  componentWillMount() {
    let requests = [];

    requests.push({
      url: 'live/admins/merchants',
      params: defaultFilters,
    });
    requests.push({ url: 'live/merchant/activation/reviewers' });

    Promise.all(requests.map(request => adminFetch(request))).then(
      ([merchants, reviewers]) => {
        // update collection items
        this.collection = new Collection({
          data: {
            url: 'live/admins/merchants',
          },
          items: merchants.items,
          fetchFn: fetchMerchants.bind(this),
          filters: { account_status: 'pending_under_review' },
        });

        //TODO: remove admin_
        reviewers.forEach(r => (r.id = r.id.replace('admin_', '')));

        this.reviewers = [{ id: '', name: 'Not Assigned' }, ...reviewers];

        this._updateMerchateReviewerMap(this.reviewers, merchants.items);
      }
    );
  }

  // create merchant -> reviewer map i.e. {merchant_id: reviewer_obj}
  _updateMerchateReviewerMap = (reviewers, merchants) => {
    let merchantReviewerMap = {};
    merchants.forEach(
      merchant =>
        (merchantReviewerMap[merchant.id] = this.getReviewer(
          merchant.merchant_detail.reviewer_id
        ))
    );
    this.setState({ pending: false, merchantReviewerMap });
  };

  _getFields = () => {
    const { selectedMerchants, merchantReviewerMap } = this.state;

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
            selected={merchantReviewerMap[item.id] || ''}
            onChange={({ option }) =>
              this.handleSingleReviewerAssignment(item.id, option)
            }
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

  getReviewer = reviewer_id => {
    return this.reviewers.find(reviewer => reviewer.id === reviewer_id);
  };

  onSubmit = filters => {
    if (filters['sub_accounts'] == 0) {
      delete filters['sub_accounts'];
    }

    return this.collection.applyFilters(filters);
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

  openBulkAssignModal = e => {
    //prevent filter form submission
    prevent(e);
    openModal(
      <BulkAssign
        reviewers={this.reviewers}
        selectedMerchants={this.state.selectedMerchants}
        onReviewerAssignment={this.handleReviewerAssignment}
      />
    );
  };

  handleSingleReviewerAssignment = (merchandId, reviewer) => {
    let merchantReviewerMap = { ...this.state.merchantReviewerMap };

    merchantReviewerMap[merchandId] = reviewer;

    this.setState({ merchantReviewerMap });

    this.handleReviewerAssignment(
      {
        reviewer_id: `admin_${reviewer.id}`,
        merchants: [merchandId],
      },
      true
    );
  };

  handleReviewerAssignment = (body, isSingleAssignment = false) => {
    let merchantReviewerMap = { ...this.state.merchantReviewerMap };
    return adminPost({
      url: 'live/merchant/activation/bulk_assign_reviewer',
      data: body,
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchants assigned successfully');
          closeModal();
          if (!isSingleAssignment) {
            body.merchants.forEach(
              merchantId =>
                (merchantReviewerMap[merchantId] = this.getReviewer(
                  body.reviewer_id.replace('admin_', '')
                ))
            );
            this.setState({ merchantReviewerMap });
          }
        }
      })
      .catch(err => notifyError(JSON.stringify(err.response)));
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
              onClick={this.openBulkAssignModal}
            >
              Bulk Assign
            </button>
          </Form>
        </div>
        <PageTable
          customClass="merchants-list"
          model={this.collection}
          fields={this._getFields()}
          animateRow={false}
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
