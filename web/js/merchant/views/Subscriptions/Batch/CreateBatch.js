import React from 'react';
import { connect } from 'react-redux';

import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  createRecurringChargeBatch,
  validateRecurringChargeBatch,
  validateRecurringChargeAxisBatch,
  createRecurringChargeAxisBatch,
  createRegistrationLinkBatch,
  validateRegistrationLinkBatch,
} from 'merchant/reducers/batches';
import { closeModal } from 'merchant_common/reducers/modals';

import RegistrationLinksBatchForm from './components/RegistrationLinkBatchCreationForm';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

class CreateHostedMandateBatch extends React.Component {
  renderRecurringChargeModal = () => (
    <BatchUpload
      acceptFileInfo={['csv']}
      createBatch={this.props.createRecurringChargeBatch}
      validateBatch={this.props.validateRecurringChargeBatch}
      gaEvents={gaEvents}
      maxRows="5,00,000"
      maxFileSize={57671680} // 55 MB
      batchType="recurring_charge"
      docUrl="https://razorpay.com/docs/recurring-payments/dashboard-operations/batch-operations/"
      sampleUrl="https://cdn.razorpay.com/dashboard/sample_recurring_payments.csv"
      processingOptions={true}
    />
  );

  renderRecurringChargeAxisModal = () => (
    <BatchUpload
      acceptFileInfo={['csv', 'xlsx']}
      createBatch={this.props.createRecurringChargeAxisBatch}
      validateBatch={this.props.validateRecurringChargeAxisBatch}
      gaEvents={gaEvents}
      maxRows="5,00,000"
      maxFileSize={57671680} // 55 MB
      batchType="recurring_charge_axis"
      docUrl="https://razorpay.com/docs/recurring-payments/dashboard-operations/batch-operations/"
      processingOptions={true}
    />
  );

  renderRegistrationLinksModal = () => (
    <BatchUpload
      acceptFileInfo={['csv']}
      createBatch={this.props.createRegistrationLinkBatch}
      validateBatch={this.props.validateRegistrationLinkBatch}
      gaEvents={gaEvents}
      maxRows="5,00,000"
      maxFileSize={57671680} // 55 MB
      batchType="auth_link"
      batchTypeText="Registration Link"
      renderBatchCreationForm={RegistrationLinksBatchForm}
      docUrl="https://razorpay.com/docs/recurring-payments/dashboard-operations/batch-operations/"
      sampleUrl="https://cdn.razorpay.com/dashboard/sample_authorization_links.csv"
    />
  );

  render() {
    const { openUploadModal, user } = this.props;

    return (
      <div className="SubscriptionsBatch--upload-modal">
        <div
          className="panel panel-default registration-link"
          onClick={openUploadModal(this.renderRegistrationLinksModal)}
        >
          <div className="panel-body">
            <div className="logo" />
            <div className="description">
              <div className="text-primary">
                <strong>Batch Registration Links</strong>
              </div>
              <div>Create Bulk Registration Links</div>
            </div>
            <i className="i-chevron-right pull-right text-primary" />
          </div>
        </div>

        {!user.isRegistrationLinkSupervisorRole && (
          <div
            className="panel panel-default recurring-charge"
            onClick={openUploadModal(
              user.isCAWRecurringChargeAxisEnabled
                ? this.renderRecurringChargeAxisModal
                : this.renderRecurringChargeModal,
            )}
          >
            <div className="panel-body">
              <div className="logo" />
              <div className="description">
                <div className="text-primary">
                  <strong>Batch Recurring Payments</strong>
                </div>
                <div>Create bulk recurring Payments</div>
              </div>
              <i className="i-chevron-right pull-right text-primary" />
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default connect((state) => ({ user: state.session.user }), {
  createRecurringChargeBatch,
  validateRecurringChargeBatch,
  validateRecurringChargeAxisBatch,
  createRecurringChargeAxisBatch,
  createRegistrationLinkBatch,
  validateRegistrationLinkBatch,
  closeModal,
})(CreateHostedMandateBatch);
