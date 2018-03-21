import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';

import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { CheckField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { openModal } from 'common/modal';
import BaseModal from 'ui/BaseModal';

import { snakeToTitleCase } from 'common/util';
import ShowWhen from 'admin/components/ShowWhen';

import * as entityModals from '../entityModals';

let parent_props, merchant_id;
const actions = {};

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
Object.keys(entityModals).map(key => {
  actions[key] = () => {
    const ModalContent = entityModals[key];
    openModal(<ModalContent props={parent_props} merchantId={merchant_id} />);
  };
});

@observer
export default class ReviewNotesDetails extends Component {
  fields = [
    [
      'Issue',
      issue => (
        <div>
          {snakeToTitleCase(issue)}{' '}
          <i
            class="pull-right delete i-trash"
            data-issuename={issue}
            onClick={this.handleDeleteOfIssue}
          />
        </div>
      ),
    ],
  ];

  handleDeleteOfIssue = e => {
    this.props.onIssueSelection(null, e.target.dataset.issuename);
  };

  handleInputChange = e => {
    this.props.activationReview[e.target.name] = e.target.value;
  };

  handleEmailGeneration = () => {
    const { issue_fields, issue_fields_reason } = this.props.activationReview;
    openModal(
      <EmailModal issues={toJS(issue_fields)} comment={issue_fields_reason} />
    );
  };

  render() {
    const {
      activationReview,
      onIssuesSubmition,
      parentProps,
      merchantId,
    } = this.props;

    const merchant = parentProps.merchant;
    const isDetailsLoading = !Object.keys(toJS(merchant.details)).length;
    const isFeaturesLoading = !Object.keys(toJS(merchant.features)).length;

    parent_props = parentProps;
    merchant_id = merchantId;

    return (
      <div class="review-notes container">
        {/* Show certain activation related action in this section also */}
        <header class="m-b">Activation Checklist: </header>
        <div class="activation-actions-btns">
          <ShowWhen permission="edit_merchant_methods">
            <button
              class="btn-default"
              onClick={isDetailsLoading ? null : actions.EditMethods}
            >
              Edit Methods
              <i class="pull-right m-l i i-money" />
              {isDetailsLoading && <div class="dot-loader">.</div>}
            </button>
          </ShowWhen>
          <ShowWhen permission="edit_merchant">
            <button
              class="btn-default"
              onClick={isDetailsLoading ? null : actions.EditMerchant}
            >
              Edit Merchant
              <i class="pull-right m-l i i-edit-form" />
              {isDetailsLoading && <div class="dot-loader">.</div>}
            </button>
          </ShowWhen>
          <ShowWhen permission="edit_merchant_pricing">
            <button
              class="btn-default"
              onClick={isDetailsLoading ? null : actions.AssignPricingPlan}
            >
              Assign Pricing
              <i class="pull-right m-l i">%</i>
              {isDetailsLoading && <div class="dot-loader">.</div>}
            </button>
          </ShowWhen>
          <ShowWhen permission="schedule_assign">
            <button
              class="btn-default"
              onClick={isDetailsLoading ? null : actions.AssignSchedule}
            >
              Assign Schedule
              <i class="pull-right m-l i i-schedule" />
              {isDetailsLoading && <div class="dot-loader">.</div>}
            </button>
          </ShowWhen>
          <ShowWhen permission="edit_merchant_tags">
            <button
              class="btn-default"
              onClick={isDetailsLoading ? null : actions.EditTags}
            >
              Tag Merchant
              <i class="pull-right m-l i i-tag" />
              {isDetailsLoading && <div class="dot-loader">.</div>}
            </button>
          </ShowWhen>
          <ShowWhen permission="edit_merchant_features">
            <button
              class="btn-default"
              onClick={isFeaturesLoading ? null : actions.EditFeatures}
            >
              Feature Merchant
              <i class="pull-right m-l i i-tag" />
              {isFeaturesLoading && <div class="dot-loader">.</div>}
            </button>
          </ShowWhen>
          <ShowWhen permission="add_merchant_credits">
            <button class="btn-default" onClick={actions.AddCredits}>
              Add Credits
              <i class="pull-right m-l i i-money" />
            </button>
          </ShowWhen>
        </div>
        <header class="m-b">Issues:</header>
        <div class="issue-section">
          <Table
            animateRow={false}
            fields={this.fields}
            items={toJS(activationReview.issue_fields)}
            customClass="table-bordered"
          />
        </div>
        {/* form for saving issues reason & internal comments */}
        <div class="issue-section">
          <Form class="full-span full-elements limited">
            <TextAreaField
              label="Reason Details"
              name="issue_fields_reason"
              placeholder="Please give a brief explanation for the reasons."
              value={activationReview.issue_fields_reason}
              onChange={this.handleInputChange}
              required={true}
            />
            <TextAreaField
              label="Internal notes"
              name="internal_notes"
              placeholder="Add an internal notes here."
              value={activationReview.internal_notes}
              onChange={this.handleInputChange}
            />
            <AsyncButton
              text="Preview Email"
              class="btn btn-default"
              pendingClass="small spinner"
              onSubmit={this.handleEmailGeneration}
            />
            <AsyncButton
              text="Save"
              class="btn"
              pendingClass="small spinner"
              onSubmit={onIssuesSubmition}
            />
          </Form>
        </div>
      </div>
    );
  }
}
/**
 * Email preview with issues listed & comments in them.
 * It's editable
 */
const EmailModal = ({ issues, comment }) => (
  <BaseModal header="Email Preview (editable)">
    <div class="email-preview" contentEditable={true}>
      <p>
        Hey,
        <br />
        <br />
        Thanks for submitting your application to us. There are few requirements
        that need to be completed before we can activate your account.
      </p>
      <div>
        Clarifications needed for :
        {/* if issue doesn't exists in the map, then use snakeTitleCase*/}
        <ul class="issues-list">
          {issues.map(issue => (
            <li key={issue}>{issuesMap[issue] || snakeToTitleCase(issue)}</li>
          ))}
        </ul>
      </div>
      <p>
        <span>Details:</span>
        <br />
        <span class="issues-comment">{comment}</span>
      </p>
      <p>
        Regards,
        <br />
        Team Razorpay
      </p>
      <p>
        <strong>
          P.S: We would need 24-48 working hours to get your responses validated
          with our partner banks. Also, Kindly avoid in-line responses. To
          report a grievance, click here:{' '}
        </strong>
        <a href="https://razorpay.com/grievances/" class="link grievance-link">
          https://razorpay.com/grievances/
        </a>
      </p>
    </div>
  </BaseModal>
);

// mapping fields with there labels used in `merchant` activation form
const issuesMap = {
  contact_name: 'Contact Name',
  contact_email: 'Email',
  transaction_report_email: 'Transaction Report Email',
  contact_mobile: 'Mobile',
  business_type: 'Organisation Type',
  business_name: 'Full Business Name',
  business_dba: 'Billing Label',
  business_paymentdetails: 'Payments Accepted for',
  business_model: 'Business Model',
  business_international: 'International Payments Required',
  business_website: 'Website/App URL',
  business_registered_address: 'Registered Address',
  business_registered_pin: 'Registered Address Pincode',
  business_registered_state: 'Registration Address State',
  business_registered_city: 'Registered Address City',
  business_operation_address: 'Operational Address',
  business_operation_pin: 'Operational Address Pincode',
  business_operation_state: 'Operational Address State',
  business_operation_city: 'Operational Address City',
  p_gstin: 'GST Identification Number',
  company_cin: 'Company CIN',
  company_pan: 'Company PAN',
  company_pan_name: 'Name on PAN',
  promoter_pan: 'PAN Number of Promoter',
  promoter_pan_name: 'Name on PAN Card',
  bank_branch_ifsc: 'IFSC Code of the Bank Branch',
  bank_account_number: 'Bank Account Number',
  bank_account_name: 'Beneficiary Name',
  business_proof: 'Business Registration Proof',
  business_operation_proof: 'Business Operation Proof',
  business_pan_proof: 'Business PAN',
  address_proof: "Company's Bank Account Statement with Address",
  promoter_proof: 'Authorised Signatory Proof',
  promoter_pan_proof: 'PAN Card',
  promoter_address_proof: "Authorised Signatory's Address Proof",
  form_12a_url: 'Form 12A Allotment Letter',
  form_80g_url: 'Form 80G Allotment Letter',
};
