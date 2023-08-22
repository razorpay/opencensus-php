import { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import SuccessModal from 'merchant/components/BatchNew/SuccessModal';
import { getCustomURL } from 'merchant/components/DocsLink';
import { luminateRow } from 'merchant/reducers/app';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import BatchCreate from './Create';
import BatchValidate from './Validate';
/**
 * Container:  Switches between validation or creation of batch.
 */

const ROUTE_SUCCESS_MESSAGE =
  'You can download the output file from batch details view to check the items which were generated. For the items that could not be generated due to some issues, please upload a new batch file.';

const successMessageMap = {
  refund:
    ' You can download the batch file report to check the final state of each refund request.',
  payment_transfer: ROUTE_SUCCESS_MESSAGE,
  linked_account_create: ROUTE_SUCCESS_MESSAGE,
  transfer_reversal: ROUTE_SUCCESS_MESSAGE,
  raw_address:
    'You can download the feedback for the addresses uploaded from the actions view, after the file is completely processed. Refresh the page to see current status of your upload. Rejected addresses will contain error description, upload a new file to rectify the same.',
  fulfillment_order_update:
    'You can download the feedback for the delivery statuses uploaded from the actions view, after the file is completely processed. Refresh the page to see current status of your upload. Rejected delivery statuses will contain error description, upload a new file to rectify the same.',
  virtual_account_edit:
    'You can download the output file from batch details view to check the items which were generated. For the items that could not be generated due to some issues, please upload a new batch file.',
};
class BatchUpload extends Component {
  state = {
    batchName: '',
    currentStatus: 'validate',
    batch: null,
  };

  handleValidation = (batch, batchName, status) => {
    this.setState({
      batch,
      batchName,
      currentStatus: status,
    });
  };

  handleCreation = (batch) => {
    this.setState((prevState) => {
      return {
        batch: { ...prevState.batch, ...batch },
        currentStatus: 'success',
      };
    });
    this.props.luminateRow(batch.id);
    this.props.trackCreateBatch && this.props.trackCreateBatch();
  };

  componentDidMount() {
    this.props.gaEvents?.trackUploadBatch('Open');
  }

  onModalClose = () => {
    const { currentStatus } = this.state;
    const { closeSuccessModal, gaEvents, closeModal, trackCloseModal } = this.props;

    gaEvents?.trackUploadBatch?.('Close');
    closeModal();

    if (currentStatus === 'success' && closeSuccessModal) {
      closeSuccessModal();
    }

    trackCloseModal && trackCloseModal();
  };

  render() {
    const {
      accept,
      onDocumentClick,
      clickToUploadAnalytics,
      onError,
      onSuccess,
      docUrl,
      batchType,
      batchTypeText,
      sampleUrl,
      gaEvents,
      validateBatch,
      maxRows,
      maxFileSize,
      acceptFileInfo,
      validateModalInfo,
      batchFormInitialValues,
      renderBatchCreationForm,
      createBatch,
      processingOptions,
      title,
      component,
      ctaText,
      successText,
      batchListClass,
      pendingText,
      disabled,
      processFile,
      modalActions,
      displayMsgs,
      isDragDropDisabled,
      hideCloseBtn,
      isSampleFileLoading,
      shouldShowSampleDownloadBtn,
      onSampleFileDownload,
    } = this.props;

    const { batchName, batch, currentStatus } = this.state;

    const docLink = getCustomURL(docUrl);

    return (
      <div className={`batch-upload-modal ${batchListClass} ${currentStatus}`}>
        <ModalHeader
          title={currentStatus !== 'success' ? title || 'Batch Upload' : ''}
          onCloseClick={this.onModalClose}
        />
        {(() => {
          switch (currentStatus) {
            case 'validate':
              return (
                <BatchValidate
                  accept={accept}
                  onValidation={this.handleValidation}
                  onSampleFileDownload={onSampleFileDownload}
                  isSampleFileLoading={isSampleFileLoading}
                  shouldShowSampleDownloadBtn={shouldShowSampleDownloadBtn}
                  docUrl={docLink}
                  onDocumentClick={onDocumentClick}
                  clickToUploadAnalytics={clickToUploadAnalytics}
                  onError={onError}
                  onSuccess={onSuccess}
                  batchType={batchType}
                  batchTypeText={batchTypeText}
                  sampleUrl={sampleUrl}
                  gaEvents={gaEvents}
                  validateBatch={validateBatch}
                  maxRows={maxRows}
                  maxFileSize={maxFileSize}
                  acceptFileInfo={acceptFileInfo}
                  modalInfo={validateModalInfo}
                  component={component}
                  disabled={disabled}
                  modalActions={modalActions}
                  processFile={processFile}
                  displayMsgs={displayMsgs}
                  isDragDropDisabled={isDragDropDisabled}
                  hideCloseBtn={hideCloseBtn}
                />
              );
            case 'create':
              return (
                <BatchCreate
                  onCreation={this.handleCreation}
                  batchName={batchName}
                  batch={batch}
                  batchType={batchType}
                  batchFormInitialValues={batchFormInitialValues}
                  renderBatchCreationForm={renderBatchCreationForm}
                  createBatch={createBatch}
                  trackUploadBatch={gaEvents?.trackUploadBatch}
                  trackSampleInterpretation={gaEvents?.trackSampleInterpretation}
                  docUrl={docLink}
                  processingOptions={processingOptions}
                  ctaText={ctaText}
                  pendingText={pendingText}
                  onFileNameTrack={this.props.onFileNameTrack}
                  onPreview={this.props.onPreview}
                  sampleUrl={sampleUrl}
                />
              );
            case 'success':
              return (
                <SuccessModal
                  onModalClose={() => {
                    this.onModalClose && this.onModalClose();
                    this.props.trackSuccessModalClose && this.props.trackSuccessModalClose();
                  }}
                  successHeader={successText}
                >
                  <p className="text-center">
                    {successMessageMap[batchType] ||
                      'You can download the output file from batch detail view to check payment links generated. For the links that could not be generated due to some issues, please upload a new batch file.'}
                    <br />
                  </p>
                </SuccessModal>
              );
            default:
              return null;
          }
        })()}
      </div>
    );
  }
}

export default connect(null, (dispatch) =>
  bindActionCreators({ closeModal, openModal, luminateRow }, dispatch),
)(BatchUpload);
