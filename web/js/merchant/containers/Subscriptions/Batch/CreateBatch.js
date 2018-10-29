import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import {
  createRecurringChargeBatch,
  validateRecurringChargeBatch,
  createAuthLinkBatch,
  validateAuthLinkBatch,
} from 'merchant/modules/batches';
import { closeModal } from 'rzp/modules/modals';

import AuthLinksBatchForm from './AuthLinkBatchCreationForm';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

@connect(null, {
  createRecurringChargeBatch,
  validateRecurringChargeBatch,
  createAuthLinkBatch,
  validateAuthLinkBatch,
  closeModal,
})
export default class CreateHostedMandateBatch extends Component {
  renderRecurringChargeModal = () => (
    <BatchUpload
      createBatch={this.props.createRecurringChargeBatch}
      validateBatch={this.props.validateRecurringChargeBatch}
      gaEvents={gaEvents}
      maxRows={5000}
      batchType="recurring_charge"
      docUrl="https://razorpay.com/docs/recurring-payments/"
    />
  );

  renderAuthLinksModal = () => (
    <BatchUpload
      createBatch={this.props.createAuthLinkBatch}
      validateBatch={this.props.validateAuthLinkBatch}
      gaEvents={gaEvents}
      maxRows={5000}
      batchType="auth_link"
      renderBatchCreationForm={AuthLinksBatchForm}
      docUrl="https://razorpay.com/docs/recurring-payments/"
    />
  );

  render() {
    const { openUploadModal } = this.props;
    return (
      <div class="SubscriptionsBatch--upload-modal">
        <div
          class="panel panel-default auth-link"
          onClick={openUploadModal(this.renderAuthLinksModal)}
        >
          <div class="panel-body">
            <div class="logo" />
            <div class="description">
              <div class="text-primary">
                <strong>Batch Authorization Links</strong>
              </div>
              <div>Create Bulk Authorization Links</div>
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
