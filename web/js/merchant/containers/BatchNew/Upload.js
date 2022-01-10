import { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import BatchValidate from './Validate';
import BatchCreate from './Create';
import SuccessModal from 'merchant/components/BatchNew/SuccessModal';
import { getCustomURL } from 'merchant/components/DocsLink';
import { bindActionCreators } from 'redux';
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
  raw_address: 'You can download the batch file to check the failed addresses',
  virtual_account_edit:
    'You can download the output file from batch details view to check the items which were generated. For the items that could not be generated due to some issues, please upload a new batch file.',
};
class BatchUpload extends Component {
  state = {
    batchName: '',
    currentStatus: 'validate',
    batch: null,
  };

  handleValidation = (batch, batchName) => {
    this.setState({
      batch,
      batchName,
      currentStatus: 'create',
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
  };

  componentDidMount() {
    this.props.gaEvents.trackUploadBatch('Open');
  }

  onModalClose = () => {
    this.props.gaEvents.trackUploadBatch('Close');
    this.props.closeModal();
  };
  render() {
    const docLink = getCustomURL(this.props.docUrl);
    return (
      <div class={`batch-upload-modal ${this.state.currentStatus}`}>
        <ModalHeader
          title={this.state.currentStatus !== 'success' ? this.props.title || 'Batch Upload' : ''}
          onCloseClick={this.onModalClose}
        />
        {(() => {
          switch (this.state.currentStatus) {
            case 'validate':
              return (
                <BatchValidate
                  onValidation={this.handleValidation}
                  batchType={this.props.batchType}
                  batchTypeText={this.props.batchTypeText}
                  sampleUrl={this.props.sampleUrl}
                  docUrl={docLink}
                  gaEvents={this.props.gaEvents}
                  validateBatch={this.props.validateBatch}
                  maxRows={this.props.maxRows}
                  maxFileSize={this.props.maxFileSize}
                  acceptFileInfo={this.props.acceptFileInfo}
                  modalInfo={this.props.validateModalInfo}
                />
              );
            case 'create':
              return (
                <BatchCreate
                  onCreation={this.handleCreation}
                  batchName={this.state.batchName}
                  batch={this.state.batch}
                  batchType={this.props.batchType}
                  batchFormInitialValues={this.props.batchFormInitialValues}
                  renderBatchCreationForm={this.props.renderBatchCreationForm}
                  createBatch={this.props.createBatch}
                  trackUploadBatch={this.props.gaEvents.trackUploadBatch}
                  trackSampleInterpretation={this.props.gaEvents.trackSampleInterpretation}
                  docUrl={docLink}
                  sampleUrl={this.props.sampleUrl}
                  processingOptions={this.props.processingOptions}
                />
              );
            case 'success':
              return (
                <SuccessModal onModalClose={this.onModalClose}>
                  <p class="text-center">
                    {successMessageMap[this.props.batchType] ||
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
