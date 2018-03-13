import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';

import fetch, { adminFetch, adminPatch, adminPut } from 'common/fetch';
import { closeModal, confirm, notifySuccess, notifyError } from 'common/modal';
import { isWorkflow } from 'common/util';

import Model from './model';
import EntityRow from 'ui/EntityRow';
import TabsContainer from 'ui/Tabs';

import ContactDetails from './merchantActivationForms/ContactDetails';
import BankAccountDetails from './merchantActivationForms/BankAccountDetails';
import DocumentDetails from './merchantActivationForms/DocumentDetails';
import ProductOnboarding from './merchantActivationForms/ProductOnboarding';
import BusinessDetails from './merchantActivationForms/BusinessDetails';
import ActivationDetails from './merchantActivationForms/ActivationDetails';
import ReviewNotesDetails from './merchantActivationForms/ReviewNotesDetails';

import { statusPill } from 'common/data';

import { Link } from 'react-router-dom';

@observer
export default class MerchantActivationForm extends Component {
  state = {};

  constructor(props) {
    super();
    this.merchantId = props.match.params.id;

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: this.merchantId,
    });
  }

  // TODO: TEST live mode to be sent here?
  componentWillMount() {
    adminFetch(`live_${this.merchantId}/invitations`).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    fetch(`/admin/api/live_${this.merchantId}/merchants-users`).then(data => {
      this.setState({
        users: data,
      });
    });
  }

  handleArchive = () => {
    const { details } = this.model.merchant;

    confirm(
      `Are you sure that you want to ${
        details.merchant_details.archived ? 'Unarchive' : 'Archive'
      } this form?`
    ).then(() => {
      adminPatch({
        url: `live/merchant/activation/${details.id}/archive`,
        data: {
          archive: details.merchant_details.archived ? 0 : 1,
        },
      }).then(response => {
        if (response) {
          if (isWorkflow(response)) {
            return;
          }
          details.merchant_details.archived = response.archived;
          notifySuccess(
            `Form ${
              response.archived ? 'archived' : 'unarchived'
            } successfully.`
          );
          closeModal();
        }
      });
    });
  };

  handleActivationStatusChange = status => {
    const { details } = this.model.merchant;

    details.merchant_details = {
      ...details.merchant_details,
      activation_status: status,
    };
  };

  getOverview() {
    const { details } = this.model.merchant;

    return (
      <div class="box">
        <div class="heading">
          Merchant:{' '}
          <Link to={`/merchants/${this.merchantId}`}>
            <b>{this.merchantId}</b>
          </Link>
        </div>
        {!Object.keys(details).length ? (
          <div class="spinner center" />
        ) : (
          _getOverviewFields
            .call(this, details)
            .map(row => (
              <EntityRow
                key={row.label}
                label={row.label}
                value={row.value}
                className="separate"
              />
            ))
        )}
      </div>
    );
  }

  handleIssueSelection = (e, resolvedIssue) => {
    this.model.editIssuesList(resolvedIssue || e.target.dataset.issuename);
  };

  handleIssuesSubmition = body => {
    const issues = this.model.activationIssuesList.peek();

    body.issue_fields = issues.join(',');

    if (!body.issue_fields_reason) {
      notifyError('Please enter a public comment.');
      return;
    }

    return adminPut({
      url: `live/merchant/activation/${this.merchantId}/update`,
      data: body,
    }).then(response => {
      notifySuccess('Review updated successfully.');
    });
  };

  handleIssueExistence = currIssue => {
    const issues = this.model.activationIssuesList.peek();
    const found = issues.findIndex(issue => currIssue === issue);
    return found > -1;
  };

  render() {
    const { details } = this.model.merchant;
    return (
      <div class="entity-container activation-form">
        <header class="heading">Activation Form</header>
        {this.getOverview()}

        <div class="box">
          <div class="heading">Merchant Activation Form</div>

          {
            <TabsContainer
              tabNames={tabNames}
              className="activation-form"
              iconClass="i i-yes text-success"
              iconBoolList={
                details.merchant_details &&
                toJS(details.merchant_details.steps_finished)
              }
            >
              <ContactDetails
                {...details}
                title={tabNames[0]}
                onIssueSelection={this.handleIssueSelection}
                doesIssueExist={this.handleIssueExistence}
              />
              <BusinessDetails
                {...details}
                title={tabNames[1]}
                onIssueSelection={this.handleIssueSelection}
                doesIssueExist={this.handleIssueExistence}
              />
              <BankAccountDetails
                {...details}
                title={tabNames[2]}
                onIssueSelection={this.handleIssueSelection}
                doesIssueExist={this.handleIssueExistence}
              />
              <DocumentDetails
                merchantId={this.merchantId}
                {...details}
                title={tabNames[3]}
                onIssueSelection={this.handleIssueSelection}
                doesIssueExist={this.handleIssueExistence}
              />
              <ProductOnboarding
                merchantId={this.merchantId}
                title={tabNames[4]}
                onIssueSelection={this.handleIssueSelection}
              />
              <ReviewNotesDetails
                title={tabNames[5]}
                onIssueSelection={this.handleIssueSelection}
                onIssuesSubmition={this.handleIssuesSubmition}
                issues={this.model.activationIssuesList.peek()}
                parentProps={this.model}
                merchantId={this.merchantId}
              />
            </TabsContainer>
          }
        </div>
      </div>
    );
  }
}

/* Resources */

function _getOverviewFields(details) {
  return [
    {
      label: 'Name',
      value: details.name,
    },
    {
      label: 'Email',
      value: details.email,
    },
    {
      label: details.merchant_details.archived
        ? 'Form is Archived'
        : 'Form is Unarchived',
      value: () =>
        details.merchant_details.activation_status ===
          'needs_clarification' && (
          <button onClick={this.handleArchive}>
            {details.merchant_details.archived ? 'Unarchive' : 'Archive'}
          </button>
        ),
    },
    {
      label: 'Activation Form Status',
      value: () =>
        details.merchant_details.allowed_next_activation_statuses.length ? (
          <ActivationDetails
            status={details.merchant_details.activation_status}
            allowedStatuses={toJS(
              details.merchant_details.allowed_next_activation_statuses
            )}
            onStatusChange={this.handleActivationStatusChange}
            merchantId={this.merchantId}
          />
        ) : (
          statusPill(details.merchant_details.activation_status) || '--'
        ),
    },
  ];
}

const tabNames = [
  'Contact Details',
  'Business Details',
  'Bank Account Details',
  'Document Uploads',
  'Product Onboading',
  'Review Notes',
];
