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
              <strong class="text-primary">Batch Authorization Links</strong>{' '}
              <i class="i-chevron-right pull-right text-primary" />
            </div>
          </div>
        </div>

        <div
          class="panel panel-default recurring-charge"
          onClick={openUploadModal(this.renderRecurringChargeModal)}
        >
          <div class="panel-body">
            <div class="logo" />
            <div class="description">
              <strong class="text-primary">Batch Payments</strong>{' '}
              <i class="i-chevron-right pull-right text-primary" />
            </div>
          </div>
        </div>
      </div>
    );
  }
}
