import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';

import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { CheckField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { openModal } from 'common/modal';

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
          <ShowWhen permission="edit_merchant_risk_threshold">
            <button
              class="btn-default"
              onClick={isDetailsLoading ? null : actions.EditFraudScore}
            >
              Edit Fraud Score
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
        <div class="issue-section">
          <Form class="full-span full-elements limited">
            <TextAreaField
              label="Public Comment"
              name="issue_fields_reason"
              placeholder="Please give a brief explanation for the reasons."
              defaultValue={activationReview.issue_fields_reason}
              required={true}
            />
            <TextAreaField
              label="Internal notes"
              name="internal_notes"
              placeholder="Add an internal notes here."
              defaultValue={activationReview.internal_notes}
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
