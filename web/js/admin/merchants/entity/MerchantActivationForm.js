import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';
import { adminFetch, adminPatch } from 'util/fetch';
import {
  openModal,
  closeModal,
  confirm,
  notifySuccess,
  notifyError,
} from 'common/modal';
import { SelectField } from 'ui/Field';
import Form from 'ui/Form';

import Model from './model';
import EntityRow from 'ui/EntityRow';
import TabsContainer from 'ui/Tabs';

import ContactDetails from './merchantActivationForms/ContactDetails';
import WebsiteDetails from './merchantActivationForms/WebsiteDetails';
import BankAccountDetails from './merchantActivationForms/BankAccountDetails';
import DocumentDetails from './merchantActivationForms/DocumentDetails';
import ProductOnboarding from './merchantActivationForms/ProductOnboarding';
import BusinessDetails from './merchantActivationForms/BusinessDetails';

import {
  NeedClarificationActivation,
  RejectActivation,
} from './merchantActivationForms/ActivationReasonModal';

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

  componentWillMount() {
    adminFetch({
      route_name: 'invitation_fetch',
      merchant_id: this.merchantId,
    }).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    adminFetch({
      route_name: 'merchant_fetch_users',
      url_params: {
        id: this.merchantId,
      },
    }).then(data => {
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
        route_name: 'merchant_activation_archive',
        url_params: {
          id: details.id,
        },
        body: {
          archive: details.merchant_details.archived ? 0 : 1,
        },
      }).then(response => {
        if (response) {
          details.merchant_details.archived = response.archived;
          notifySuccess(
            `Form ${
              response.archived ? 'archived' : 'unarchived'
            } updated successfully.`
          );
          closeModal();
        }
      });
    });
  };

  openActivationModal = body => {
    const prevStatus = this.model.merchant.details.merchant_details
      .activation_status;
    //check whether status has changed or is undefined/null/empty
    if (!body.activation_status) {
      notifyError('Please select a status from the drop down menu.');
      return;
    }

    if (body.activation_status === 'rejected') {
      openModal(
        <RejectActivation
          status={body.activation_status}
          fetchFn={this.updateActivationStatus}
        />
      );
      return;
    }

    if (body.activation_status === 'needs_clarification') {
      openModal(
        <NeedClarificationActivation fetchFn={this.updateActivationStatus} />
      );
      return;
    }

    confirm(`Change Status to ${statusMap[body.activation_status]}?`).then(
      () => {
        this.updateActivationStatus({
          activation_status: body.activation_status,
        });
      }
    );
  };

  updateActivationStatus = body => {
    const { details } = this.model.merchant;

    return adminPatch({
      route_name: 'merchant_activation_status',
      url_params: {
        id: this.merchantId,
      },
      body,
    }).then(response => {
      if (response) {
        details.merchant_details.activation_status = response.activation_status;
        notifySuccess('Status updated successfully.');
        closeModal();
      }
    });
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

  render() {
    const { details } = this.model.merchant;
    return (
      <div class="entity-container">
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
              <ContactDetails {...details} title={tabNames[0]} />
              <BusinessDetails {...details} title={tabNames[1]} />
              <WebsiteDetails {...details} title={tabNames[2]} />
              <BankAccountDetails {...details} title={tabNames[3]} />
              <DocumentDetails
                merchantId={this.merchantId}
                {...details}
                title={tabNames[4]}
              />
              <ProductOnboarding
                merchantId={this.merchantId}
                title={tabNames[5]}
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
      value: () => (
        <button onClick={this.handleArchive}>
          {details.merchant_details.archived ? 'Unarchive' : 'Archive'}
        </button>
      ),
    },
    {
      label: 'Activation Form Status',
      value: () => (
        <Form onSubmit={this.openActivationModal}>
          <SelectField
            name="activation_status"
            defaultValue={details.merchant_details.activation_status || ''}
          >
            <option value="">Select status</option>
            {Object.keys(statusMap).map(status => (
              <option key={status} value={status}>
                {statusMap[status]}
              </option>
            ))}
          </SelectField>
          <button>Change</button>
        </Form>
      ),
    },
  ];
}

const tabNames = [
  'Contact Details',
  'Business Details',
  'Website details',
  'Bank Account Details',
  'Document Uploads',
  'Product Onboading',
];

const statusMap = {
  under_review: 'Under Review',
  needs_clarification: 'Needs Clarification',
  activated: 'Activated',
  rejected: 'Rejected',
};
