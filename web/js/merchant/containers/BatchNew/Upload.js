import { Component } from 'react';
import { connect } from 'react-redux';

import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal, openModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';

import BatchValidate from './Validate';
import BatchCreate from './Create';
import SuccessModal from 'merchant/components/BatchNew/SuccessModal';

/**
 * Container:  Switches between validation or creation of batch.
 */

@connect(null, { closeModal, openModal, luminateRow })
export default class BatchUpload extends Component {
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

  handleCreation = batch => {
    this.setState({
      batch: { ...this.state.batch, ...batch },
      currentStatus: 'success',
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
    return (
      <div class={`batch-upload-modal ${this.state.currentStatus}`}>
        <ModalHeader
          title={this.state.currentStatus !== 'success' ? 'Batch Upload' : ''}
          onCloseClick={this.onModalClose}
        />
        {(() => {
          switch (this.state.currentStatus) {
            case 'validate':
              return (
                <BatchValidate
                  onValidation={this.handleValidation}
                  batchType={this.props.batchType}
                  sampleUrl={this.props.sampleUrl}
                  docUrl={this.props.docUrl}
                  gaEvents={this.props.gaEvents}
                />
              );
            case 'create':
              return (
                <BatchCreate
                  onCreation={this.handleCreation}
                  batchName={this.state.batchName}
                  batch={this.state.batch}
                  batchType={this.props.batchType}
                  maxRows={this.props.maxRows}
                  batchFormInitialValues={this.props.batchFormInitialValues}
                  renderBatchCreationForm={this.props.renderBatchCreationForm}
                  trackUploadBatch={this.props.gaEvents.trackUploadBatch}
                  trackSampleInterpretation={
                    this.props.gaEvents.trackSampleInterpretation
                  }
                />
              );
            case 'success':
              return (
                <SuccessModal onModalClose={this.onModalClose}>
                  <p class="text-center">
                    You can download the output file from batch detail view to
                    check payment links generated. For the links that could not
                    be generated due to some issues, please upload a new batch
                    file.
                    <br />
                  </p>
                </SuccessModal>
              );
            case 'default':
              return null;
          }
        })()}
      </div>
    );
  }
}
