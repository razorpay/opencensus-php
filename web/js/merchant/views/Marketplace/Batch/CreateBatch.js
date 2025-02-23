import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withSplitzService } from 'common/splitz';
import TwoFactorVerificationContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { isExperimentActive } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { isOrgFeatureExist } from 'merchant/models/User';
import {
  createTransferBatch,
  validateTransferBatch,
  createLinkedAccountBatch,
  createLinkedAccountBatchWithAccountCode,
  validateLinkedAccountBatch,
  validateLinkedAccountBatchWithAccountCode,
  createReversalsBatch,
  validateReversalsBatch,
} from 'merchant/reducers/batches';
import { closeModal } from 'merchant_common/reducers/modals';
import { isAccountCodeEnabled } from 'merchant/views/Settlements/components/utils';

const gaEvents = setGaTrack('Dashboard - Route - BU');

class CreateHostedMandateBatch extends Component {
  renderTransfersModal = () => (
    <BatchUpload
      acceptFileInfo={['csv', 'xlsx']}
      createBatch={this.props.createTransferBatch}
      validateBatch={this.props.validateTransferBatch}
      gaEvents={gaEvents}
      maxRows="50,000"
      maxFileSize={11534336} // 11 MB
      batchType="payment_transfer"
      docUrl="https://razorpay.com/docs/route/dashboard/batch-upload/"
      sampleUrl="/files/sample_batch_payment_transfer.xlsx"
      processingOptions={true}
    />
  );

  renderLinkedAccountsModal = (isACEnabled) => {
    const batchProps = isACEnabled
      ? {
          createBatch: this.props.createLinkedAccountBatchWithAccountCode,
          validateBatch: this.props.validateLinkedAccountBatchWithAccountCode,
          batchType: 'linked_account_create_with_account_code',
          sampleUrl: '/files/sample_batch_linked_account_v2.xlsx',
        }
      : {
          createBatch: this.props.createLinkedAccountBatch,
          validateBatch: this.props.validateLinkedAccountBatch,
          batchType: 'linked_account_create',
          sampleUrl: '/files/sample_batch_linked_account.xlsx',
        };

    return (
      <BatchUpload
        {...batchProps}
        acceptFileInfo={['csv', 'xlsx']}
        gaEvents={gaEvents}
        maxRows="50,000"
        maxFileSize={11 * 1024 * 1024} // 11 MB
        docUrl="https://razorpay.com/docs/route/dashboard/batch-upload/"
        processingOptions
      />
    );
  };

  renderReversalsModal = () => (
    <BatchUpload
      acceptFileInfo={['csv', 'xlsx']}
      createBatch={this.props.createReversalsBatch}
      validateBatch={this.props.validateReversalsBatch}
      gaEvents={gaEvents}
      maxRows="50,000"
      maxFileSize={11534336} // 11 MB
      batchType="transfer_reversal"
      docUrl="https://razorpay.com/docs/route/dashboard/batch-upload/"
      sampleUrl="/files/sample_batch_reversals.xlsx"
      processingOptions={true}
    />
  );

  openLinkedAccountsModal = ({ criticalFlow, isExpEnabled, isACEnabled }) => {
    if (isExpEnabled) {
      criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: this.props.openUploadModal(() =>
          this.renderLinkedAccountsModal(isACEnabled),
        ),
      });
    } else {
      this.props.openUploadModal(() => this.renderLinkedAccountsModal(isACEnabled))();
    }
  };

  render() {
    const { openUploadModal, user, splitz } = this.props;
    const isACEnabled = isAccountCodeEnabled(splitz) && user.isRouteCodeSupportEnabled;

    const {
      abExperiments: { enable_2fa_batch_upload = {} },
    } = splitz;

    const is2FAExpEnabled = isExperimentActive(enable_2fa_batch_upload);

    return (
      <div className="RouteBatch--dropdown">
        <div className="panel panel-default" onClick={openUploadModal(this.renderTransfersModal)}>
          <div className="panel-body">
            <img src={require('assets/marketplace/transfers.svg')} />
            <div className="description">
              <div className="text-primary">
                <strong>Transfers</strong>
              </div>
              <div>Create transfers in batch</div>
            </div>
            <i className="i-chevron-right pull-right text-primary" />
          </div>
        </div>
        <div className="panel panel-default" onClick={openUploadModal(this.renderReversalsModal)}>
          <div className="panel-body">
            <img src={require('assets/marketplace/reversals.svg')} />
            <div className="description">
              <div className="text-primary">
                <strong>Reversals</strong>
              </div>
              <div>Create reversals in batch</div>
            </div>
            <i className="i-chevron-right pull-right text-primary" />
          </div>
        </div>
        <ShowWhen
          additionalCondition={() =>
            !user.isRouteLinkedAccountCreationDisabled && !isOrgFeatureExist('block_account_update')
          }
        >
          <TwoFactorVerificationContext.Consumer>
            {({ criticalFlow }) => (
              <div
                className="panel panel-default"
                onClick={() =>
                  this.openLinkedAccountsModal({
                    criticalFlow,
                    isExpEnabled: is2FAExpEnabled,
                    isACEnabled,
                  })
                }
              >
                <div className="panel-body">
                  <img src={require('assets/marketplace/linked_accounts.svg')} />
                  <div className="description">
                    <div className="text-primary">
                      <strong>Linked accounts</strong>
                    </div>
                    <div>Create linked accounts in a batch</div>
                  </div>
                  <i className="i-chevron-right pull-right text-primary" />
                </div>
              </div>
            )}
          </TwoFactorVerificationContext.Consumer>
        </ShowWhen>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ user: state.session.user }), {
    createTransferBatch,
    validateTransferBatch,
    createLinkedAccountBatch,
    validateLinkedAccountBatch,
    validateLinkedAccountBatchWithAccountCode,
    createLinkedAccountBatchWithAccountCode,
    createReversalsBatch,
    validateReversalsBatch,
    closeModal,
  }),
  withSplitzService,
)(CreateHostedMandateBatch);
