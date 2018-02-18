import { Component } from 'react';
import { connect } from 'react-redux';

import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal, openModal } from 'rzp/modules/modals';

import BatchValidate from './Validate';
import BatchCreate from './Create';

/**
 * Container:  Switches between validation or creation of batch.
 */

@connect(null, { closeModal, openModal })
export default class BatchUpload extends Component {
  state = {
    currentStatus: 'validate',
    batch: null,
  };

  handleSuccess = () => {};

  handleValidation = batch => {
    this.setState({
      batch,
      currentStatus: 'create',
    });
  };

  handleCreation = batch => {
    this.setState({
      batch: { ...this.state.batch, ...batch },
      currentStatus: 'success',
    });
  };

  render() {
    return (
      <div class={`batch-upload-modal ${this.state.currentStatus}`}>
        <ModalHeader
          title="Batch Upload"
          onCloseClick={this.props.closeModal}
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
                />
              );
            case 'create':
              return (
                <BatchCreate
                  onCreation={this.handleCreation}
                  batch={this.state.batch}
                  batchType={this.props.batchType}
                />
              );
            case 'success':
              return (
                <BatchSuccess
                  name={this.state.batch.name}
                  success_count={this.state.batch.success_count}
                  total_count={this.state.batch.total_count}
                />
              );
            case 'default':
              return null;
          }
        })()}
      </div>
    );
  }
}

const BatchSuccess = ({ success_count, total_count, name }) => (
  //TODO: remove inline styles
  <div class="modal-body">
    The batch{' '}
    {name ? (
      <span>
        titled <strong>{name}</strong>{' '}
      </span>
    ) : null}{' '}
    is created succesfully. A total of {success_count} rows were processed out
    of {total_count}. You can try processing the remaining rows by uploading a
    new batch file.
  </div>
);
