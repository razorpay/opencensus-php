import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import ModalHeader from 'rzp/ui/ModalHeader';

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
  state = { type: '' };

  setType = type => () => {
    this.setState({ type });
  };

  render() {
    switch (this.state.type) {
      case 'recurring_charge':
        return (
          <BatchUpload
            createBatch={this.props.createRecurringChargeBatch}
            validateBatch={this.props.validateRecurringChargeBatch}
            gaEvents={gaEvents}
            maxRows={5000}
            batchType="recurring_charge"
          />
        );
      case 'auth_link':
        return (
          <BatchUpload
            createBatch={this.props.createAuthLinkBatch}
            validateBatch={this.props.validateAuthLinkBatch}
            gaEvents={gaEvents}
            maxRows={5000}
            batchType="auth_link"
            renderBatchCreationForm={AuthLinksBatchForm}
          />
        );
      default:
        return (
          <div class="batch-upload-modal hosted-emandate-batch-upload">
            <ModalHeader
              title="Create Batch File"
              onCloseClick={this.props.closeModal}
            />
            <div class="modal-body">
              <div
                class="panel panel-default auth-link"
                onClick={this.setType('auth_link')}
              >
                <div class="panel-body">
                  <div class="logo" />
                  <div class="description">
                    <strong class="text-primary">
                      Batch Authorization Links
                    </strong>{' '}
                    <i class="i-chevron-right pull-right text-primary" />
                  </div>
                </div>
              </div>

              <div
                class="panel panel-default recurring-charge"
                onClick={this.setType('recurring_charge')}
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
          </div>
        );
    }
  }
}
