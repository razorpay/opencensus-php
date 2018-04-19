import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import { observer } from 'mobx-react';

import {
  openModal,
  notifyError,
  notifySuccess,
  closeModal,
} from 'common/modal';
import Form from 'ui/Form';
import BaseModal from 'ui/BaseModal';
import AsyncButton from 'ui/AsyncButton';
import Field, { SelectField, TextAreaField, FileField } from 'ui/Field';
import Table from 'ui/Table';
import { statusPill, publicFeature } from 'common/data';
import { isWorkflow, prevent } from 'common/util';
import { snakeToTitleCase, formatDate } from 'common/util';

import { adminFetch, adminPatch } from 'common/fetch';

import PreviewEmail from './PreviewEmail';

@observer
export default class EditPublicFeatures extends Component {
  state = {
    pending: true,
    agreement: null,
    selectedStatus: this.props.model.status,
    selectedReasonCategory: null,
    needs_clarification_text: '',
  };

  akaFeature = this.props.model.name;

  // TODO: TEST Sending mode as live, not sent earlier
  featureRequestUrls = [
    `live/merchant/requests/${this.props.model.id}`,
    'live/merchant/requests/rejection_reasons',
  ];

  componentWillMount() {
    let requests = [];

    requests = this.featureRequestUrls.map(url =>
      adminFetch({
        url,
      })
    );

    Promise.all(requests).then(([feature, allRejectionReasons]) => {
      let newState = { pending: false };

      if (feature) {
        this.submissions = feature.submissions;
        if (feature.name === 'marketplace') {
          newState.agreement = feature.submissions.vendor_agreement;
        }
        if (feature.needs_clarification_text) {
          newState.needs_clarification_text = needs_clarification_text;
        }
      }

      newState.selectedReasonCategory = Object.keys(allRejectionReasons)[0];

      this.statusLogs = feature.states.items;
      this.allowed_next_activation_statuses =
        feature.allowed_next_activation_statuses;
      this.allRejectionReasons = allRejectionReasons;

      this.setState(newState);
    });
  }

  //bool to check whether the status has changed & its `needs_clarification`
  hasStatusChangedToNeedClarification = () => {
    //current status
    const { selectedStatus } = this.state;
    //old status
    const { status } = this.props.model;

    return (
      selectedStatus === 'needs_clarification' && status !== selectedStatus
    );
  };

  changeAgreement = () => {
    this.setState({ agreement: null });
  };

  generateClarificationEmail = () => {
    const { akaFeature } = this;
    let { needs_clarification_text } = this.state;

    // add extra texts based on product type
    const addons = {
      marketplace: {
        prefix:
          'The sample vendor agreement uploaded does not meet our requirements. Please ensure that the agreement contains the below points.',
        suffix:
          'Please reply to this email with the updated sample vendor agreement so that we can take further course of action.',
      },
      others: {
        suffix:
          'Please reply to this email, with the necessary details,  so that we can take further course of action.',
      },
    };

    if (
      publicFeature.featuresAkaMap[akaFeature] ===
      publicFeature.featuresAkaMap.marketplace
    ) {
      return `${
        addons[akaFeature].prefix
      }<br/><br/>Clarifications: <br/>${needs_clarification_text}<br/><br/>${
        addons[akaFeature].suffix
      }`;
    } else {
      return `Clarifications: <br/>${needs_clarification_text}<br/><br/>${
        addons.others.suffix
      }`;
    }
  };

  save = body => {
    const { akaFeature, hasStatusChangedToNeedClarification } = this;
    const { selectedStatus, needs_clarification_text } = this.state;

    if (hasStatusChangedToNeedClarification()) {
      if (needs_clarification_text.length === 0) {
        notifyError('Add Clarification Email text to proceed.');
        return;
      } else {
        //sanitize code
        body.needs_clarification_text = encodeURIComponent(
          this.generateClarificationEmail()
        );
      }
    }

    return adminPatch({
      url: `live/merchant/requests/${this.props.model.id}`,
      data: body,
    }).then(response => {
      if (response) {
        closeModal();
        if (isWorkflow(response)) {
          notifySuccess('Workflow is created successfully.');
          return;
        }
        notifySuccess('Submission edited successfully.');
        //re-render list
        this.props.fetchFn();
      }
    });
  };

  handleStatusChange = e => {
    this.setState({
      selectedStatus: e.target.value,
    });
  };

  handleRejectionCategoryChange = e => {
    this.setState({
      selectedReasonCategory: e.target.value,
    });
  };

  handleClarificationTextChange = needs_clarification_text => {
    this.setState({ needs_clarification_text });
  };

  openEmailPreview = event => {
    prevent(event);
    const { name } = this.props.model;
    const { needs_clarification_text } = this.state;

    openModal(
      <PreviewEmail
        productName={name}
        needs_clarification_text={needs_clarification_text}
        onClarificationTextChange={this.handleClarificationTextChange}
      />
    );
  };

  render() {
    let {
      selectedStatus,
      selectedReasonCategory,
      needs_clarification_text,
    } = this.state;

    let { merchant_id, name, internal_comment } = this.props.model;

    let {
      akaFeature,
      submissions,
      changeAgreement,
      allRejectionReasons,
      hasStatusChangedToNeedClarification,
      save,
    } = this;

    let oldStatus = this.props.model.status;

    return (
      <BaseModal header="Edit Submission">
        <Form class="full-span full-elements" onSubmit={save}>
          {this.state.pending ? (
            <div class="spinner center" />
          ) : (
            <div>
              <div class="field">
                <label>Merchant ID</label>
                <code>{merchant_id}</code>
              </div>
              <div class="field">
                <label>Feature</label>
                <code>{name}</code>
              </div>
              <SelectField
                label="Status"
                name="status"
                value={selectedStatus}
                onChange={this.handleStatusChange}
                helpMsg={
                  hasStatusChangedToNeedClarification() && (
                    <span>
                      Add Clarification Email text by clicking{' '}
                      <span
                        class="link no-underline"
                        onClick={this.openEmailPreview}
                      >
                        here
                      </span>{' '}
                      to proceed.
                    </span>
                  )
                }
              >
                <option value={oldStatus}>{snakeToTitleCase(oldStatus)}</option>
                {this.allowed_next_activation_statuses.map(status => (
                  <option value={status} key={status}>
                    {snakeToTitleCase(status)}
                  </option>
                ))}
              </SelectField>

              {/* rejection functionality for product activation */}
              {selectedStatus === 'rejected' && (
                <Fragment>
                  <SelectField
                    label="Select Rejection Category:"
                    name="rejection_reason[reason_category]"
                    value={selectedReasonCategory}
                    onChange={this.handleRejectionCategoryChange}
                  >
                    {Object.keys(allRejectionReasons).map(reasonCategory => (
                      <option value={reasonCategory} key={reasonCategory}>
                        {snakeToTitleCase(reasonCategory)}
                      </option>
                    ))}
                  </SelectField>

                  {/* hide reason_code field for now as reason_category has only one reason_code. */}
                  <div style={{ display: 'none' }}>
                    <SelectField
                      label="Select Rejection Reasons:"
                      name="rejection_reason[reason_code]"
                    >
                      {allRejectionReasons[selectedReasonCategory].map(
                        reason => (
                          <option value={reason.code} key={reason.code}>
                            {reason.description}
                          </option>
                        )
                      )}
                    </SelectField>
                  </div>
                </Fragment>
              )}
              {publicFeature.featuresAkaMap[akaFeature] ===
                publicFeature.featuresAkaMap.marketplace ||
              publicFeature.featuresAkaMap[akaFeature] ===
                publicFeature.featuresAkaMap.virtual_accounts ? (
                <TextAreaField
                  label="Use Case"
                  name="submissions[use_case]"
                  defaultValue={submissions.use_case}
                />
              ) : null}

              {publicFeature.featuresAkaMap[akaFeature] ===
                publicFeature.featuresAkaMap.virtual_accounts && (
                <Field
                  label="Expected Monthly Revenue"
                  type="number"
                  name="submissions[expected_monthly_revenue]"
                  defaultValue={submissions.expected_monthly_revenue}
                />
              )}

              {publicFeature.featuresAkaMap[akaFeature] ===
                publicFeature.featuresAkaMap.subscriptions && [
                <TextAreaField
                  label="Business Model"
                  name="submissions[business_model]"
                  key="business_model"
                  defaultValue={submissions.business_model}
                />,
                <TextAreaField
                  label="Subscription Plans"
                  name="submissions[sample_plans]"
                  key="sample_plans"
                  defaultValue={submissions.sample_plans}
                />,
                <TextAreaField
                  label="Website Details"
                  name="submissions[website_details]"
                  key="website_details"
                  defaultValue={submissions.website_details}
                />,
              ]}

              {publicFeature.featuresAkaMap[akaFeature] ===
                publicFeature.featuresAkaMap.marketplace && [
                <SelectField
                  label="Transferring to"
                  name="submissions[settling_to]"
                  key="settling_to"
                  defaultValue={submissions.settling_to}
                >
                  {publicFeature.tranferToOptions.map(t => (
                    <option key={t[0]} value={t[0]}>
                      {t[1]}
                    </option>
                  ))}
                </SelectField>,
                <div key="vendor_agreement">
                  {this.state.agreement ? (
                    <div>
                      <div class="field">
                        <label>Signed Vendor Agreement:</label>
                        <a
                          class="link"
                          href={this.state.agreement}
                          target="_blank"
                        >
                          <i class="i-download" />&nbsp;Vendor Agreement
                        </a>
                        &nbsp;&nbsp;&nbsp;
                        <div class="link danger" onClick={changeAgreement}>
                          Change
                        </div>
                      </div>
                    </div>
                  ) : (
                    <FileField
                      label="Signed Vendor Agreement:"
                      name="file_name"
                    />
                  )}
                </div>,
              ]}

              <TextAreaField
                label="Internal Comment"
                name="internal_comment"
                defaultValue={internal_comment}
              />

              <Table items={this.statusLogs} fields={statusLogsFields} />

              <AsyncButton
                text="Save"
                class="btn"
                pendingClass="small spinner"
                onSubmit={save}
              />

              {hasStatusChangedToNeedClarification() && (
                <button class="btn btn-default" onClick={this.openEmailPreview}>
                  Add Clarification Text
                </button>
              )}
            </div>
          )}
        </Form>
      </BaseModal>
    );
  }
}

export function showEntity(fetchFn) {
  openModal(<EditPublicFeatures fetchFn={fetchFn} model={this} />);
}

const statusLogsFields = [
  ['Created At', item => formatDate(item.created_at)],
  ['Activation Status', item => statusPill(item.name)],
  [
    'Rejection Reason',
    item =>
      item.rejection_reasons.count ? (
        <div style={{ maxWidth: '100px' }}>
          {snakeToTitleCase(item.rejection_reasons.items[0]['reason_category'])}
        </div>
      ) : (
        '--'
      ),
  ],
];
