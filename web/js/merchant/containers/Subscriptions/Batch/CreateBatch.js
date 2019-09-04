import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import {
  createRecurringChargeBatch,
  validateRecurringChargeBatch,
  createRegistrationLinkBatch,
  validateRegistrationLinkBatch,
} from 'merchant/modules/batches';
import { closeModal } from 'rzp/modules/modals';

import RegistrationLinksBatchForm from './RegistrationLinkBatchCreationForm';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

@connect(null, {
  createRecurringChargeBatch,
  validateRecurringChargeBatch,
  createRegistrationLinkBatch,
  validateRegistrationLinkBatch,
  closeModal,
})
export default class CreateHostedMandateBatch extends Component {
  renderRecurringChargeModal = () => (
    <BatchUpload
      createBatch={this.props.createRecurringChargeBatch}
      validateBatch={this.props.validateRecurringChargeBatch}
      gaEvents={gaEvents}
      maxRows="10,000"
      batchType="recurring_charge"
      docUrl="https://razorpay.com/docs/recurring-payments/"
      sampleUrl="https://cdn.razorpay.com/dashboard/sample_recurring_payments.csv"
    />
  );

  renderRegistrationLinksModal = () => (
    <BatchUpload
      createBatch={this.props.createRegistrationLinkBatch}
      validateBatch={this.props.validateRegistrationLinkBatch}
      gaEvents={gaEvents}
      maxRows="10,000"
      batchType="auth_link"
      renderBatchCreationForm={RegistrationLinksBatchForm}
      docUrl="https://razorpay.com/docs/recurring-payments/"
      sampleUrl="https://cdn.razorpay.com/dashboard/sample_authorization_links.csv"
    />
  );

  render() {
    const { openUploadModal } = this.props;

    return (
      <div class="SubscriptionsBatch--upload-modal">
        <div
          class="panel panel-default auth-link"
          onClick={openUploadModal(this.renderRegistrationLinksModal)}
        >
          <div class="panel-body">
            <div class="logo" />
            <div class="description">
              <div class="text-primary">
                <strong>Batch Registration Links</strong>
              </div>
              <div>Create Bulk Registration Links</div>
            </div>
            <i class="i-chevron-right pull-right text-primary" />
          </div>
        </div>

        <div
          class="panel panel-default recurring-charge"
          onClick={openUploadModal(this.renderRecurringChargeModal)}
        >
          <div class="panel-body">
            <div class="logo" />
            <div class="description">
              <div class="text-primary">
                <strong>Batch Recurring Payments</strong>
              </div>
              <div>Create bulk recurring Payments</div>
            </div>
            <i class="i-chevron-right pull-right text-primary" />
          </div>
        </div>
      </div>
    );
  }
}
